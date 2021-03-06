<?php
namespace Router;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Router;
use Symfony\Bundle\FrameworkBundle\Routing\DelegatingLoader;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;

/**
 * 
 */
class ProjectRouter
{
    /**
     * @var string
     */
    protected $routingDir;

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
     * @var string
     */
    protected $cacheDir;

    /**
     * @param  string $routingDir The directory to look for the routing resource
     * @param  mixed $resource The routing resource to source from
     * @param  boolean $debug Whether to load the router in debug mode
     */
    public function __construct($routingDir, $resource, $debug = false, $cacheDir)
    {
        $this->routingDir = $routingDir;
        $this->resource = $resource;
        $this->debug = $debug;
        $this->cacheDir = $cacheDir;
    }

    /**
     * @return Router
     */
    public function getRouter()
    {
        if ($this->router === null) {
            $loader = new YamlFileLoader(new FileLocator($this->routingDir));

            $cacheDir = $this->cacheDir.'/'.($this->debug ? 'debug' : 'prod');
            
            if (!file_exists($cacheDir)) {
                if (!file_exists(dirname($cacheDir))) {
                    throw new \Exception(sprintf('Cache directory "%s" does not exist.', dirname($cacheDir)));
                }

                if (!is_writable(dirname($cacheDir))) {
                    throw new \Exception(sprintf('Cache directory "%s" is not writable.', dirname($cacheDir)));
                }

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
        $context = new RequestContext(
            $request->getBaseUrl(),
            $request->getMethod(),
            $request->getHost(),
            $request->getScheme()
        );
        $this->getRouter()->setContext($context);

        try {
            $parameters = $this->getRouter()->match($request->getPathInfo());

            if (!isset($parameters['_app'])) {
                $route = $parameters['_route'];
                throw new \InvalidArgumentException(sprintf('No "_app" parameter specified for route "%s"', $route));
            }

            return $parameters['_app'];
        } catch (ResourceNotFoundException $e) {
            return $default;
        }
    }
}