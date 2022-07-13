<?php

namespace Zeaven\EasySuit\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

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

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            // 自定义异常报告，如sentry
        });

        $this->renderable(fn (Throwable $e, $request) => $this->customRender($e, $request));
    }

    private function customRender(Throwable $e, $request)
    {

        if ($request->method() === 'GET' && $request->headers->get('content-type') !== 'application/json') {
            // 页面请求不做处理
            return null;
        }

        if (method_exists($e, 'getErrorCode')) {
            $errorCode = $e->getErrorCode();
        } elseif (method_exists($e, 'getStatusCode')) {
            $errorCode = $e->getStatusCode();
        } else {
            $errorCode = $e->getCode() ?: 500;
        }

        $response = [
            'code' => $errorCode,
            'data' => null,
            'message' => $e->getMessage(),
            'error' => $e->getTraceAsString()
        ];

        if ($e instanceof ValidationException) {
            $response['message'] = head($e->errors())[0];
        } elseif ($e instanceof NotFoundHttpException) {
            $response['message'] = 'Invalid access address';
        } elseif ($e instanceof AuthenticationException) {
            $response['code'] = 401;
            $response['message'] = __('error_code.401');
        } elseif ($e instanceof QueryException) {
            $response['code'] = 500;
            $response['message'] = __('error_code.401');
        } elseif (is_numeric($errorCode) && $errorCode > 1000) {
            $response['message'] = __('error_code.' . dechex($errorCode));
        }

        return ok($response);
    }
}
