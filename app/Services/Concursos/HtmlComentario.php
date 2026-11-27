<?php

namespace App\Services\Concursos;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Limpa o HTML do comentário editado no navegador: só formatação simples, links http(s) e imagens
 * enviadas como anexo do material (/concursos/material/anexos/N). Todo o resto vira texto.
 */
class HtmlComentario
{
    private const TAGS = ['p', 'br', 'b', 'strong', 'i', 'em', 'u', 's', 'ul', 'ol', 'li', 'h3', 'h4', 'blockquote', 'mark', 'a', 'img', 'pre', 'code', 'div', 'span'];

    private const SOLTAR = ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'template', 'noscript', 'head', 'title', 'meta', 'link'];

    public static function limpar(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }
        $doc = new DOMDocument('1.0', 'UTF-8');
        $anterior = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"?><div id="raiz">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($anterior);

        $raiz = $doc->getElementById('raiz');
        if (!$raiz) {
            return e(strip_tags($html));
        }
        self::filhos($raiz);

        $out = '';
        foreach (iterator_to_array($raiz->childNodes) as $n) {
            $out .= $doc->saveHTML($n);
        }
        $semConteudo = trim(strip_tags($out, '<img>')) === '';

        return $semConteudo ? '' : trim($out);
    }

    /** Texto puro (como o do caderno) em parágrafos HTML. */
    public static function deTexto(?string $texto): string
    {
        $blocos = preg_split('/\n\s*\n/u', trim((string) $texto)) ?: [];

        return implode('', array_map(fn ($b) => '<p>' . nl2br(e(trim($b)), false) . '</p>', array_filter($blocos, fn ($b) => trim($b) !== '')));
    }

    private static function filhos(DOMNode $pai): void
    {
        foreach (iterator_to_array($pai->childNodes) as $n) {
            if ($n instanceof DOMElement) {
                self::elemento($n);
            } elseif ($n->nodeType === XML_COMMENT_NODE || $n->nodeType === XML_PI_NODE) {
                $pai->removeChild($n);
            }
        }
    }

    private static function elemento(DOMElement $el): void
    {
        $tag = strtolower($el->tagName);
        if (in_array($tag, self::SOLTAR, true)) {
            $el->parentNode->removeChild($el);

            return;
        }
        self::filhos($el);
        if (!in_array($tag, self::TAGS, true)) {
            // Tag desconhecida: fica só o conteúdo.
            while ($el->firstChild) {
                $el->parentNode->insertBefore($el->firstChild, $el);
            }
            $el->parentNode->removeChild($el);

            return;
        }

        $attrs = [];
        if ($tag === 'a') {
            $href = trim($el->getAttribute('href'));
            if (preg_match('#^https?://#i', $href)) {
                $attrs = ['href' => $href, 'target' => '_blank', 'rel' => 'noopener noreferrer'];
            }
        } elseif ($tag === 'img') {
            $src = trim($el->getAttribute('src'));
            $src = preg_replace('#^https?://[^/]+#i', '', $src);
            if (!preg_match('#^/concursos/material/anexos/\d+$#', $src)) {
                $el->parentNode->removeChild($el);

                return;
            }
            $attrs = ['src' => $src, 'alt' => mb_substr($el->getAttribute('alt'), 0, 200)];
        }
        foreach (iterator_to_array($el->attributes) as $a) {
            $el->removeAttribute($a->nodeName);
        }
        foreach ($attrs as $k => $v) {
            $el->setAttribute($k, $v);
        }
    }
}
