<?php

namespace App\Http\Controllers;

use App\Models\ConcursoState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ConcursosController extends Controller
{
    private const ALLOWED_PAGES = [
        'inicio.html',
        'caderno.html',
        'simulados.html',
        'flashcards.html',
        'refazer.html',
        'resumos.html',
        'leis.html',
    ];

    public function index(Request $request): RedirectResponse
    {
        // Onde o módulo novo está ligado: quem segue o planejamento dirigido cai na meta do dia;
        // quem não segue, direto no material de resumo. O app estático fica para onde ele está desligado.
        if (config('concursos.enabled')) {
            return redirect()->route($request->user()?->temPlanoConcursos() ? 'concursos.meta' : 'concursos.material.index');
        }

        return redirect()->route('concursos.page', ['page' => 'inicio.html']);
    }

    public function page(string $page): Response
    {
        if (!in_array($page, self::ALLOWED_PAGES, true)) {
            abort(404);
        }

        $path = resource_path("concursos/{$page}");
        if (!is_file($path)) {
            abort(404);
        }

        $html = file_get_contents($path);
        $csrfMeta = '<meta name="csrf-token" content="' . e(csrf_token()) . '">';

        if (stripos($html, '<head>') !== false) {
            $html = preg_replace('/<head>/i', "<head>\n{$csrfMeta}", $html, 1);
        } else {
            $html = $csrfMeta . $html;
        }

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function asset(string $path): BinaryFileResponse
    {
        if (str_contains($path, '..') || str_contains($path, "\0")) {
            abort(404);
        }

        $baseDir = realpath(public_path('concursos-app'));
        $fullPath = $baseDir ? realpath($baseDir . DIRECTORY_SEPARATOR . $path) : false;

        if (!$baseDir || !$fullPath || !str_starts_with($fullPath, $baseDir . DIRECTORY_SEPARATOR) || !is_file($fullPath)) {
            abort(404);
        }

        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mimeTypes = [
            'css' => 'text/css; charset=utf-8',
            'js' => 'application/javascript; charset=utf-8',
            'json' => 'application/json; charset=utf-8',
            'md' => 'text/markdown; charset=utf-8',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'woff2' => 'font/woff2',
        ];

        $headers = ['Cache-Control' => 'no-cache'];
        if (isset($mimeTypes[$ext])) {
            $headers['Content-Type'] = $mimeTypes[$ext];
        }

        return response()->file($fullPath, $headers);
    }

    public function getState(Request $request): JsonResponse
    {
        $rows = ConcursoState::query()
            ->where('user_id', $request->user()->id)
            ->pluck('valor', 'chave')
            ->all();

        return response()
            ->json((object) $rows)
            ->header('Cache-Control', 'no-store');
    }

    public function saveState(Request $request): JsonResponse
    {
        $payload = $request->json()->all();
        if (!is_array($payload)) {
            return response()->json(['ok' => false], 400);
        }

        $set = $payload['set'] ?? [];
        $del = $payload['del'] ?? [];

        if (!is_array($set) || !is_array($del)) {
            return response()->json(['ok' => false], 400);
        }

        $userId = $request->user()->id;
        $now = now();

        DB::transaction(function () use ($userId, $set, $del, $now) {
            if (!empty($set)) {
                $rows = [];
                foreach ($set as $chave => $valor) {
                    $rows[] = [
                        'user_id' => $userId,
                        'chave' => (string) $chave,
                        'valor' => is_scalar($valor) || $valor === null ? (string) $valor : json_encode($valor),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                ConcursoState::upsert(
                    $rows,
                    ['user_id', 'chave'],
                    ['valor', 'updated_at']
                );
            }

            if (!empty($del)) {
                $keys = array_map(static fn ($k) => (string) $k, $del);
                ConcursoState::query()
                    ->where('user_id', $userId)
                    ->whereIn('chave', $keys)
                    ->delete();
            }
        });

        return response()
            ->json(['ok' => true])
            ->header('Cache-Control', 'no-store');
    }
}
