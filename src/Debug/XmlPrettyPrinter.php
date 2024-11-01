<?php declare(strict_types=1);
namespace Dgm\UspsSimple\Debug;

use Exception;


class XmlPrettyPrinter
{
    /**
     * @template T
     * @param T $value
     * @return T
     */
    public static function tryPettyPrint($value)
    {
        if (!is_string($value) || $value === "" || !class_exists('DOMDocument', false)) {
            return $value;
        }

        $prefix = '';
        $xmlStart = strpos($value, '<');
        if ($xmlStart !== false && $xmlStart !== 0) {
            $prefix = ltrim(substr($value, 0, $xmlStart));
            if ($prefix !== '' && substr($prefix, -1) !== "\n") {
                $prefix .= "\n";
            }
            $value = substr($value, $xmlStart);
        }

        $value = self::make($value);

        $value = $prefix . $value;

        return $value;
    }

    private static function make(string $value): string
    {
        $xmlHeaderRegex = '|^\\s*<\\?xml[^>]+\\?>\\n?|';
        $hadXmlHeaderOriginally = preg_match($xmlHeaderRegex, $value);
        $stripXmlHeaderIfNeeded = static function($value) use ($xmlHeaderRegex, $hadXmlHeaderOriginally) {
            if (!$hadXmlHeaderOriginally) {
                $value = preg_replace($xmlHeaderRegex, '', $value);
            }
            return $value;
        };

        try {
            /** @noinspection PhpComposerExtensionStubsInspection : it's an optional dependency */
            $doc = new \DOMDocument('1.0');
            $doc->preserveWhiteSpace = false;
            $doc->formatOutput = true;
            $doc->substituteEntities = false;

            if (@$doc->loadXML($value, LIBXML_HTML_NODEFDTD | LIBXML_NONET)) {

                $res = @$doc->saveXML();
                if (is_string($res)) {

                    $res = $stripXmlHeaderIfNeeded($res);

                    $res = preg_replace_callback('|^([ ]+)<|m', static function($m) {
                        return str_repeat($m[1], 2) . '<';
                    }, $res);

                    /** @noinspection CallableParameterUseCaseInTypeContextInspection */
                    $value = $res;
                }
            }
        }
        catch (Exception $e) {
            // just return the input as is
        }

        return $value;
    }
}