<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FinancialSummaryService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TvWidgetController extends Controller
{
    public function resumo(Request $request): JsonResponse
    {
        $expectedToken = config('services.tv_widget.token', env('TV_WIDGET_TOKEN', 'philco_glass_tv_secure_token'));
        $providedToken = $request->header('X-TV-Token') ?? $request->query('token');

        if ($expectedToken && $providedToken !== $expectedToken) {
            return response()->json([
                'status' => 'error',
                'message' => 'Não autorizado'
            ], 401);
        }

        $userId = config('telegram.user_id');
        $user = User::find($userId) ?? User::first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Nenhum usuário cadastrado'
            ], 404);
        }

        $agora = Carbon::now();
        $mes = (int) $request->query('mes', $agora->month);
        $ano = (int) $request->query('ano', $agora->year);

        $summary = FinancialSummaryService::generateSummary($user, $mes, $ano);

        $nomeMes = Carbon::create($ano, $mes, 1)->translatedFormat('F');

        // Limpa formatação Markdown das dicas para visualização limpa na TV
        $dicasFormatadas = array_map(function ($dica) {
            return preg_replace('/[*_`]/', '', $dica);
        }, array_slice($summary['dicas'] ?? [], 0, 3));

        return response()->json([
            'status' => 'success',
            'mes' => $mes,
            'ano' => $ano,
            'mes_extenso' => ucfirst($nomeMes),
            'saldo_cc' => (float) ($summary['saldo_cc'] ?? 0.0),
            'entradas_realizadas' => (float) ($summary['entradas_realizadas'] ?? 0.0),
            'entradas_faltam' => (float) ($summary['entradas_faltam'] ?? 0.0),
            'total_entradas' => (float) ($summary['total_entradas'] ?? 0.0),
            'saidas_realizadas' => (float) ($summary['saidas_realizadas'] ?? 0.0),
            'saidas_faltam' => (float) ($summary['saidas_faltam'] ?? 0.0),
            'total_saidas' => (float) ($summary['total_saidas'] ?? 0.0),
            'faturas_abertas' => (float) ($summary['faturas_abertas'] ?? 0.0),
            'resultado_liquido_mes' => (float) ($summary['resultado_liquido_mes'] ?? 0.0),
            'saldo_final_previsto' => (float) ($summary['saldo_final_conta'] ?? 0.0),
            'dicas' => $dicasFormatadas,
            'atualizado_em' => $agora->format('d/m/Y H:i'),
        ]);
    }

    public function controlUi()
    {
        return view('tv_control');
    }

    public function action(Request $request): JsonResponse
    {
        $type = $request->input('type');

        switch ($type) {
            case 'open_url':
                $url = trim($request->input('url', ''));
                if (empty($url)) {
                    return response()->json(['status' => 'error', 'message' => 'URL vazia'], 422);
                }
                if (!preg_match('#^https?://#i', $url)) {
                    $url = 'https://' . $url;
                }
                $this->executeAdb('shell "am start -a android.intent.action.VIEW -d \'' . addslashes($url) . '\'"');
                return response()->json(['status' => 'success', 'message' => 'URL aberta na TV!']);

            case 'text':
                $text = (string) $request->input('text', '');
                if ($text === '') {
                    return response()->json(['status' => 'error', 'message' => 'Texto vazio'], 422);
                }
                $encodedText = str_replace([' ', '&', '<', '>', ';', '|', '"', "'"], ['%s', '\&', '\<', '\>', '\;', '\|', '\"', "\'"], $text);
                $this->executeAdb('shell input text "' . $encodedText . '"');
                return response()->json(['status' => 'success', 'message' => 'Texto enviado para a TV!']);

            case 'key':
                $key = strtolower($request->input('key', ''));
                $keycodes = [
                    'up' => 19,
                    'down' => 20,
                    'left' => 21,
                    'right' => 22,
                    'enter' => 66,
                    'back' => 4,
                    'home' => 3,
                    'volup' => 24,
                    'voldown' => 25,
                    'mute' => 164,
                ];
                if (!isset($keycodes[$key])) {
                    return response()->json(['status' => 'error', 'message' => 'Tecla inválida'], 422);
                }
                $this->executeAdb('shell input keyevent ' . $keycodes[$key]);
                return response()->json(['status' => 'success', 'message' => 'Tecla enviada!']);

            case 'app':
                $app = strtolower($request->input('app', ''));
                $packages = [
                    'browser' => 'com.phlox.tvwebbrowser',
                    'youtube' => 'com.google.android.youtube.tv',
                    'netflix' => 'com.netflix.ninja',
                    'jellyfin' => 'org.jellyfin.androidtv',
                ];
                if (!isset($packages[$app])) {
                    return response()->json(['status' => 'error', 'message' => 'App não suportado'], 422);
                }
                $this->executeAdb('shell monkey -p ' . $packages[$app] . ' -c android.intent.category.LAUNCHER 1');
                return response()->json(['status' => 'success', 'message' => 'App aberto na TV!']);

            default:
                return response()->json(['status' => 'error', 'message' => 'Ação não reconhecida'], 400);
        }
    }

    private function executeAdb(string $cmd): array
    {
        $adb = 'C:\\Users\\SaMuB\\AppData\\Local\\Android\\Sdk\\platform-tools\\adb.exe';
        if (!file_exists($adb)) {
            $adb = 'C:\\Users\\SaMuB\\scrcpy\\adb.exe';
        }
        $ip = '192.168.15.7:5555';
        exec('"' . $adb . '" connect ' . $ip);
        $fullCmd = '"' . $adb . '" -s ' . $ip . ' ' . $cmd;
        exec($fullCmd, $output, $returnCode);
        return ['output' => $output, 'code' => $returnCode];
    }
}
