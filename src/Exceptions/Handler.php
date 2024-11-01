<?php

namespace Zeaven\EasySuit\Exceptions;

use Str;
use Throwable;
use Illuminate\Database\QueryException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use App\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];


    public function render($request, Throwable $e): Response
    {
        $_global_response = $request->attributes->get('_global_response');
        $matchGlobal = false;
        if ($_global_response === false) {
            return parent::render($request, $e);
        }
        if ($_global_response === null) {
            if ($include_routes = config('easy_suit.global_response.include', [])) {
                foreach ($include_routes as $include_route) {
                    if ($request->is($include_route)) {
                        $matchGlobal = true;
                        break;
                    }
                }
            }
        }
        if (!$_global_response && !$matchGlobal) {
            return parent::render($request, $e);
        }

        // if ($request->method() === 'GET' && !Str::contains($request->headers->get('content-type'), 'json')) {
        //     // 页面请求不做处理
        //     return ok('');
        // }

        if (method_exists($e, 'getErrorCode')) {
            $errorCode = $e->getErrorCode();
        } elseif (method_exists($e, 'getStatusCode')) {
            $errorCode = $e->getStatusCode();
        } else {
            $errorCode = $e->getCode() ?: 500;
        }

        $result = [
            'code' => $errorCode,
            'data' => null,
            'message' => $e->getMessage(),
            'error' => $e->getTraceAsString()
        ];

        if ($e instanceof ValidationException) {
            $result['message'] = head($e->errors())[0];
        } elseif ($e instanceof NotFoundHttpException) {
            $result['message'] = __('error_code.404');
        } elseif ($e instanceof AuthenticationException) {
            $result['code'] = 401;
            $result['message'] = __('error_code.401');
        } elseif ($e instanceof QueryException) {
            $result['code'] = 500;
            $result['message'] = __('error_code.500');
        } elseif (is_numeric($errorCode) && $errorCode > 1000) {
            $result['message'] = __('error_code.' . dechex($errorCode));
        }

        if (!$_global_response && $matchGlobal) {
            $fields = config('easy_suit.global_response.fields', []);
            $response = [];
            foreach ($fields as $key => $value) {
                if (!$value || !array_key_exists($key, $result)) {
                    continue;
                }
                $response[$value] = $result[$key];
            }

            return ok($response);
        } else {
            return ok($result);
        }
    }
}
