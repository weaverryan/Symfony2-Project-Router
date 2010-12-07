<?php
namespace Router;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Router;
use Symfony\Bundle\FrameworkBundle\Routing\DelegatingLoader;
use Symfony\Component\Routing\Loader\YamlFileLoader;

/**
 * 
 */
class ProjectRouter
{
    /**
     * @var mixed
     */
    protected $resource;

    /**
     * @var boolean
     */
    protected $debug;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @param  mixed $resource The routing resource to source from
     * @param  boolean $debug Whether to load the router in debug mode
     */
    public function __construct($resource, $debug = false)
    {
        $this->resource = $resource;
        $this->debug = $debug;
    }

    /**
     * @return Router
     */
    public function getRouter()
    {
        if ($this->router === null) {
            $loader = new YamlFileLoader(__DIR__.'/routing');

            $cacheDir = __DIR__.'/cache/'.($this->debug ? 'debug' : 'prod');
            if (!file_exists($cacheDir))
            {
                mkdir($cacheDir, 0777);
            }

            $this->router = new Router($loader, $this->resource, array(
                'cache_dir' => $cacheDir,
                'debug'     => $this->debug,
            ));
        }

        return $this->router;
    }

    /**
     * Manually set the router
     *
     * @param string Router $router The router to use.
     */
    public function setRouter(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Returns the application that should be used for the current request.
     *
     * This matches against the routing and looks for an _app parameter.
     *
     * @param Request $request The current request object to match on
     * @param string $default The default to return if no application is matched
     * @return string
     */
    public function matchApplication(Request $request, $default = null)
    {
        // allows a ?app_force=XXX to be added to any url
        if ($this->debug && $app = $request->get('app_force')) {
            return $app;
        }

        // set the context information on the router
        $this->getRouter()->setContext(array(
            'base_url'  => $request->getBaseUrl(),
            'method'    => $request->getMethod(),
            'host'      => $request->getHost(),
            'is_secure' => $request->isSecure(),
        ));

        if (false !== $parameters = $this->getRouter()->match($request->getPathInfo())) {
            if (!isset($parameters['_app'])) {
                $route = $parameters['_route'];
                throw new \InvalidArgumentException(sprintf('No "_app" parameter specified for route "%s"', $route));
            }

            return $parameters['_app'];
        }

        return $default;
    }
}