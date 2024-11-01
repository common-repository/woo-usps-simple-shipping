<?php declare(strict_types=1);
namespace Dgm\UspsSimple\Calc\Error;


class Error extends \Exception
{
    public function __construct(string $msg, array $data = [])
    {
        parent::__construct($msg . self::stringify($data));
    }

    private static function stringify(array $data): string
    {
        if (!$data) {
            return "";
        }

        $res = @json_encode($data,
            JSON_PRETTY_PRINT |
            JSON_UNESCAPED_SLASHES |
            JSON_UNESCAPED_UNICODE |
            JSON_PRESERVE_ZERO_FRACTION |
            JSON_PARTIAL_OUTPUT_ON_ERROR
        );
        if ($res === false) {
            $e = json_last_error_msg();
            $res = "[json_encode error: $e]";
        }

        return ". Data: $res.";
    }
}