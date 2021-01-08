<?php


namespace EasySwoole\Http\GlobalParam;


use EasySwoole\Session\Session;
use EasySwoole\Spl\SplBean;

class Config extends SplBean
{
    protected $cookieExpire = 0;
    protected $cookiePath = '/';
    protected $cookieDomain = '';
    protected $cookieSecure = false;
    protected $cookieHttponly = false;
    protected $cookieSameSite = '';
    protected $session;
    protected $sessionName;

    public function enableSession(Session $session,string $sessionName = 'es_session'):Config
    {
        $this->session = $session;
        $this->sessionName = $sessionName;
        return $this;
    }

    public function getCookieExpire(): int
    {
        return $this->cookieExpire;
    }

    public function setCookieExpire(int $cookieExpire): Config
    {
        $this->cookieExpire = $cookieExpire;
        return $this;
    }

    public function getCookiePath(): string
    {
        return $this->cookiePath;
    }

    public function setCookiePath(string $cookiePath): Config
    {
        $this->cookiePath = $cookiePath;
        return $this;
    }

    public function getCookieDomain(): string
    {
        return $this->cookieDomain;
    }

    public function setCookieDomain(string $cookieDomain): Config
    {
        $this->cookieDomain = $cookieDomain;
        return $this;
    }

    public function isCookieSecure(): bool
    {
        return $this->cookieSecure;
    }

    public function setCookieSecure(bool $cookieSecure): Config
    {
        $this->cookieSecure = $cookieSecure;
        return $this;
    }

    public function isCookieHttponly(): bool
    {
        return $this->cookieHttponly;
    }

    public function setCookieHttponly(bool $cookieHttponly): Config
    {
        $this->cookieHttponly = $cookieHttponly;
        return $this;
    }

    public function getCookieSameSite(): string
    {
        return $this->cookieSameSite;
    }

    public function setCookieSameSite(string $cookieSameSite): Config
    {
        $this->cookieSameSite = $cookieSameSite;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @param mixed $session
     */
    public function setSession($session): void
    {
        $this->session = $session;
    }

    /**
     * @return mixed
     */
    public function getSessionName()
    {
        return $this->sessionName;
    }

    /**
     * @param mixed $sessionName
     */
    public function setSessionName($sessionName): void
    {
        $this->sessionName = $sessionName;
    }
}