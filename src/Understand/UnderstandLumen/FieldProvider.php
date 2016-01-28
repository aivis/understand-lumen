<?php namespace Understand\UnderstandLumen;

use Understand\UnderstandLumen\TokenProvider;
use \Illuminate\Session\Store AS SessionStore;
use Illuminate\Http\Request;

class FieldProvider
{

    /**
     * The registered field providers.
     *
     * @var array
     */
    protected $providers = [];

    /**
     * Default field
     *
     * @var array
     */
    protected $defaultProviders = [
        'getSessionId',
        'getUrl',
        'getRequestMethod',
        'getServerIp',
        'getClientIp',
        'getClientUserAgent',
        'getEnvironment',
        'getFromSession',
        'getProcessIdentifier',
        'getUserId'
    ];

    /**
     * Session store
     *
     * @var \Illuminate\Session\Store
     */
    protected $session;

    /**
     * Server variable
     *
     * @var Request
     */
    protected $request;

    /**
     * Token provider
     *
     * @var TokenProvider
     */
    protected $tokenProvider;

    /**
     * Current environment
     *
     * @var string
     */
    protected $environment;

    /**
     * Create field provider instance and set default providers to provider list
     *
     * @param type $app
     * @return void
     */
    public function __construct()
    {
        foreach ($this->defaultProviders as $defaultProviderName)
        {
            $this->extend($defaultProviderName, [$this, $defaultProviderName]);
        }
    }

    /**
     * Set session store
     *
     * @param type $service
     */
    public function setSessionStore(SessionStore $service)
    {
        $this->session = $service;
    }

    /**
     * Set request
     *
     * @param Request $request
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Set current environment
     *
     * @param string $environment
     */
    public function setEnvironment($environment)
    {
        $this->environment = $environment;
    }

    /**
     * Register a custom HTML macro.
     *
     * @param string $name
     * @param  mixed  $provider
     * @return void
     */
    public function extend($name, $provider)
    {
        $this->providers[$name] = $provider;
    }

    /**
     * Set token provider
     *
     * @param TokenProvider $tokenProvider
     */
    public function setTokenProvider(TokenProvider $tokenProvider)
    {
        $this->tokenProvider = $tokenProvider;
    }

    /**
     * Handle class calls
     *
     * @param string $name
     * @param  mixed $params
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($name, $params)
    {
        if (isset($this->providers[$name]))
        {
            return call_user_func_array($this->providers[$name], $params);
        }

        throw new \BadMethodCallException("Method {$name} does not exist.");
    }

    /**
     * Return hashed version of session id
     *
     * @return string
     */
    protected function getSessionId()
    {
        if ( ! $this->session)
        {
            return null;
        }

        $sessionId = $this->session->getId();

        // by default we provide only hashed version of session id
        $hashed = sha1($sessionId);

        return $hashed;
    }

    /**
     * Return current url
     *
     * @return string
     */
    protected function getUrl()
    {
        $url = $this->request->path();

        if ( ! starts_with($url, '/'))
        {
            $url = '/' . $url;
        }

        $queryString = $this->request->getQueryString();

        if ($queryString)
        {
            $url .= '?' . $queryString;
        }

        return $url;
    }

    /**
     * Return request method
     *
     * @return string
     */
    protected function getRequestMethod()
    {
        return $this->request->method();
    }

    /**
     * Return server ip address
     *
     * @return string
     */
    protected function getServerIp()
    {
        return $this->request->server->get('SERVER_ADDR');
    }

    /**
     * Return client ip
     *
     * @return string
     */
    protected function getClientIp()
    {
        return $this->request->getClientIp();
    }

    /**
     * Return client user agent string
     *
     * @return string
     */
    protected function getClientUserAgent()
    {
        return $this->request->server->get('HTTP_USER_AGENT');
    }

    /**
     * Return current enviroment
     *
     * @return string
     */
    protected function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * Retrive parameter from current session
     *
     * @param string $key
     * @return string
     */
    protected function getFromSession($key)
    {
        if ( ! $this->session)
        {
            return null;
        }

        return $this->session->get($key);
    }

    /**
     * Return current active user id
     *
     * @return int
     */
    protected function getUserId()
    {
        try
        {
            if (class_exists('\Auth') && ($userId = \Auth::id()))
            {
                return $userId;
            }
        }
        catch (\Exception $e)
        {}
        try
        {
            if (class_exists('\Sentinel') && ($user = \Sentinel::getUser()))
            {
                return $user->id;
            }
        }
        catch (\Exception $e)
        {}

        try
        {
            if (class_exists('\Sentry') && ($user = \Sentry::getUser()))
            {
                return $user->id;
            }
        }
        catch (\Exception $e)
        {}
    }

    /**
     * Return process identifier token
     *
     * @return string
     */
    protected function getProcessIdentifier()
    {
        return $this->tokenProvider->getToken();
    }
}