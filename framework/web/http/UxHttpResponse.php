<?php
/**
 * Response object to be constructed by the views.
 *
 * When constructing a view, the response object must be populated and
 * returned. The response is then displayed to the visitor. 
 * The interest of using a response object is that we can run a post
 * filter action on the response. For example you can run a filter that
 * is checking that all the output is valid HTML and write a logfile if
 * this is not the case.
 */
class UxHttpResponse
{
    /**
     * Content of the response.
     */
    public $content = '';

    /**
     * Array of the headers to add.
     *
     * For example $this->headers['Content-Type'] = 'text/html; charset=utf-8';
     */
    public $headers = array();

    /**
     * Status code of the answer.
     */
    public $statusCode = 200;

    /**
     * Cookies to send.
     *
     * $this->cookies['my_cookie'] = 'content of the cookie';
     */
    public $cookies = array();

    /**
     * Status code list.
     *
     * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
     */
    public $status_code_list = array(
                                     '100' => 'CONTINUE',
                                     '101' => 'SWITCHING PROTOCOLS',
                                     '200' => 'OK',
                                     '201' => 'CREATED',
                                     '202' => 'ACCEPTED',
                                     '203' => 'NON-AUTHORITATIVE INFORMATION',
                                     '204' => 'NO CONTENT',
                                     '205' => 'RESET CONTENT',
                                     '206' => 'PARTIAL CONTENT',
                                     '300' => 'MULTIPLE CHOICES',
                                     '301' => 'MOVED PERMANENTLY',
                                     '302' => 'FOUND',
                                     '303' => 'SEE OTHER',
                                     '304' => 'NOT MODIFIED',
                                     '305' => 'USE PROXY',
                                     '306' => 'RESERVED',
                                     '307' => 'TEMPORARY REDIRECT',
                                     '400' => 'BAD REQUEST',
                                     '401' => 'UNAUTHORIZED',
                                     '402' => 'PAYMENT REQUIRED',
                                     '403' => 'FORBIDDEN',
                                     '404' => 'NOT FOUND',
                                     '405' => 'METHOD NOT ALLOWED',
                                     '406' => 'NOT ACCEPTABLE',
                                     '407' => 'PROXY AUTHENTICATION REQUIRED',
                                     '408' => 'REQUEST TIMEOUT',
                                     '409' => 'CONFLICT',
                                     '410' => 'GONE',
                                     '411' => 'LENGTH REQUIRED',
                                     '412' => 'PRECONDITION FAILED',
                                     '413' => 'REQUEST ENTITY TOO LARGE',
                                     '414' => 'REQUEST-URI TOO LONG',
                                     '415' => 'UNSUPPORTED MEDIA TYPE',
                                     '416' => 'REQUESTED RANGE NOT SATISFIABLE',
                                     '417' => 'EXPECTATION FAILED',
                                     '500' => 'INTERNAL SERVER ERROR',
                                     '501' => 'NOT IMPLEMENTED',
                                     '502' => 'BAD GATEWAY',
                                     '503' => 'SERVICE UNAVAILABLE',
                                     '504' => 'GATEWAY TIMEOUT',
                                     '505' => 'HTTP VERSION NOT SUPPORTED'
                                     );

 
    /**
     * Constructor of the response.
     *
     * @param string Content of the response ('')
     * @param string MimeType of the response (null) if not given will
     * default to the one given in the configuration 'mimetype'
     */
    public function __construct($content='', $mimetype=null)
    {
        if (is_null($mimetype)) {
            $mimetype = 'text/html; charset=utf-8';
        }
        $this->content = $content;
        $this->headers['Content-Type'] = $mimetype;
        $this->headers['X-Powered-By'] = 'Uxf - http://www.uuxoo.com/';
        $this->statusCode = 200;
        $this->cookies = array();
    }

    /**
     * Render a response object.
     */
    public function render($output_body = true) {
        if ($this->statusCode >= 200 
            && $this->statusCode != 204 
            && $this->statusCode != 304) {
            $this->headers['Content-Length'] = strlen($this->content);
        }
        $this->outputHeaders();
        if ($output_body) {
            echo $this->content;
        }
    }

    /**
     * Output headers.
     */
    public function outputHeaders()
    {
        header('HTTP/1.0 ' . $this->statusCode . ' '
                . $this->status_code_list[$this->statusCode], true, $this->statusCode);
        foreach ($this->headers as $header => $ch) {
            header($header . ': ' . $ch);
        }
        foreach ($this->cookies as $cookie => $data) {
            // name, data, expiration, path, domain, secure, http only
            $expire = (null == $data) ? time() - 31536000 : time() + 31536000;
            $data = (null == $data) ? '' : $data;
            UxHttpCookie::set($cookie, $data, $expire);//TODO: 设置cookie域名及路径
        }
    }
}
