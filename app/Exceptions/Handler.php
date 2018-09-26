<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use App\Library\Readability\ParseException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthenticationException::class,
        AuthorizationException::class,
        HttpException::class,
        HttpResponseException::class,
        ModelNotFoundException::class,
        TokenMismatchException::class,
        ValidationException::class,
        JSMSErrorException::class,
        MethodNotAllowedHttpException::class,
        UnauthorizedHttpException::class,
        ParseException::class
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * @var int
     */
    protected $statusCode = 200;

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     * @throws Exception
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Exception $exception)
    {
        if ($request->expectsJson()) {
            switch(true) {
                case  $exception instanceof AuthenticationException:
                    return $this->unauthenticated($request, $exception);
                case $exception instanceof ModelNotFoundException:
                    return $this->respondNotFound();
                case $exception instanceof MethodNotAllowedHttpException:
                    return $this->respondMethodNotAllowed();
                case $exception instanceof NotFoundHttpException:
                    return $this->respondNotFound('Unsupported API path!');
                case $exception instanceof AuthorizationException:
                    return $this->respondAccessDenied();
                case $exception instanceof UnauthorizedHttpException:
                    return $this->respondInvalidCredentials();
                case $exception instanceof WechatErrorException:
                    return $exception->toResponse($request);
                case $exception instanceof UsageErrorException:
                    return $exception->toResponse($request);
                case $exception instanceof JSMSErrorException:
                    return $exception->toResponse($request);
                case $exception instanceof ParseException:
                    return $this->respondParseException($exception);
                case $exception instanceof ValidationException:
                    return $this->convertValidationExceptionToResponse($exception, $request);
                default:
                    return $this ->respondInternalError($exception);
            }
        }
        return parent::render($request, $exception);
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return $this->respondUnauthenticated();
        }
        return parent::unauthenticated($request, $exception);
    }

    /**
     * Create a response object from the given validation exception.
     *
     * @param  \Illuminate\Validation\ValidationException  $e
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function convertValidationExceptionToResponse(ValidationException $e, $request)
    {
        if ($request->expectsJson()) {
            $message = $e->validator->errors()->first();
            return $this->respondInvalidParameterError($e, $message);
        }
        return parent::convertValidationExceptionToResponse($e, $request);
    }

    /**
     * @return mixed
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }


    /**
     * @param $statusCode
     * @return $this
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * @param string $message
     * @return mixed
     */
    public function  respondNotFound($message = 'Not Found!') {
        return $this->setStatusCode(404)->respondWithError($message,'NOT_FOUND',ErrorCode::$NOT_FOUND);
    }

    /**
     * @return mixed
     */
    public function  respondMethodNotAllowed() {
        return $this->setStatusCode(405)->respondWithError('Method Not Allowed!','METHOD_NOT_ALLOWED',ErrorCode::$METHOD_NOT_ALLOWED);
    }


    /**
     * @return mixed
     */
    public function  respondAccessDenied() {
        return $this->setStatusCode(403)->respondWithError('Access Denied!','ACTION_NOT_ALLOWED',ErrorCode::$ACTION_NOT_ALLOWED);
    }

    /**
     * @return mixed
     */
    public function  respondInvalidCredentials() {
        return $this->setStatusCode(401)->respondWithError('The user credentials were incorrect.','INVALID_CREDENTIALS',ErrorCode::$INVALID_CREDENTIALS);
    }


    /**
     * @return mixed
     */
    public function  respondUnauthenticated() {
        return $this->setStatusCode(401)->respondWithError('Unauthorized!','UNAUTHORIZED_ACCESS',ErrorCode::$UNAUTHORIZED_ACCESS);
    }


    /**
     * @param Exception $e
     * @return mixed
     */
    public function  respondInternalError($e) {
        return $this->setStatusCode(500)->respondWithError('Internal Server Error!', 'INTERNAL_ERROR',ErrorCode::$INTERNAL_ERROR, $e);
    }

    /**
     * @param $message
     * @param Exception $e
     * @return mixed
     */
    public function  respondInvalidParameterError($e, $message = 'Invalid parameter!') {
        return $this->setStatusCode(400)->respondWithError($message, 'INVALID_PARAMETER',ErrorCode::$INVALID_PARAMETER, $e);
    }

    /**
     * @param Exception $e
     * @return mixed
     */
    public function  respondParseException($e) {
        return $this->setStatusCode(400)->respondWithError($e -> getMessage(),'READABILITY_PARSE_FAILED',ErrorCode::$READABILITY_PARSE_FAILED);
    }

    /**
     * @param $message
     * @param null $humanCode
     * @param null $code
     * @param Exception $e
     * @return mixed
     */
    public function respondWithError($message, $humanCode=null, $code=null, Exception $e=null)
    {
        $response = [
            'error' => [
                'message' => $message,
                'code' => $code,
                'text' => $humanCode,
            ]
        ];
        if ($humanCode==='INTERNAL_ERROR' && config('app.debug')) {
            $response['debug'] = [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())->map(function ($trace) {
                    return Arr::except($trace, ['args']);
                })->all(),
            ];
        }
        return response()->json($response, $this -> getStatusCode());
    }
}
