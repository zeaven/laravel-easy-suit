<?php

namespace Zeaven\EasySuit\Exceptions;

use App\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
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
        parent::reportable(function (Throwable $e) {
            // 自定义异常报告，如sentry
        });

        parent::renderable(fn (Throwable $e, $request) => $this->customRender($e, $request));
    }

    private function customRender(Throwable $e, $request)
    {
        $_global_response = $request->attributes->get('_global_response');
        $matchGlobal = false;
        if ($_global_response === false) {
            return;
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
            return;
        }

        if ($request->method() === 'GET' && $request->headers->get('content-type') !== 'application/json') {
            // 页面请求不做处理
            return;
        }

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
            $result['message'] = __('error_code.401');
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

            return $response;
        } else {
            return ok($result);
        }
    }
}
