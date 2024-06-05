<?php


namespace PixellWeb\Myrentcar\app;


class MyrentcarException extends \Exception
{

    protected $response;

    /**
     * ReferentielApiException constructor.
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct($message = "", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        \Log::channel('myrentcar')->alert($message);
    }


    public function getResponse() :?array
    {
        return $this->getPrevious() ? json_decode((string) $this->getPrevious()->getResponse()->getBody(), true) : null;
    }

    public function getResponseMessage() :?string
    {
        return $this->getResponse()['Message'] ?? null;
    }

    public function getResponseAction() :?string
    {
        return $this->getResponse()['Action'] ?? null;
    }

    public function getResponseErreurs() :?string
    {
        return $this->getResponse()['Erreurs'] ?? null;
    }



}
