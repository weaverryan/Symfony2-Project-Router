Project Router
==============

A simple library that uses the Symfony2 routing component to route a specific
request between two applications. A routing file is used to determine which
application (e.g. version1 or version2) to load.

The use-case is a site that is built partially in symfony1 and partially in
Symfony2. In this case, the front controller may actually bootstrap the symfony1
project or the Symfony2 project. This library is a convenient way to keep that
logic in a familiar routing configuration file.

First, create a `project_router.php` file and place it in the web directory:

    <?php
    use Symfony\Component\HttpFoundation\Request;
    use Router\ProjectRouter;
    require_once __DIR__.'/../src/Router/ProjectRouter.php';

    function handle_project_routing(Request $request, $environment, $debug)
    {
        $router = new ProjectRouter(__DIR__.'/../app/config', 'project_routing.yml', $debug);
        $app = $router->matchApplication($request, 'v1');

        if ($app == 'v2') {
            $kernel = new AppKernel($environment, $debug);
            $kernel->handle($request)->send();
        } elseif ($app == 'v1')  {
            $path = __DIR__.'/../src/vendor/v1/web';
            $controller = $debug ? 'frontend_dev.php' : 'index.php';
            require $path.'/'.$controller;
        } else {
            throw new Exception(sprintf('Invalid application "%s"', $app));
        }
    }

This file is an example, and bootstraps a symfony1 application placed in the
`src/vendor/v1` directory.

The individual front controllers simply call this new file.

    <?php

    require_once __DIR__.'/../app/AppKernel.php';
    require_once __DIR__.'/project_router.php';

    use Symfony\Component\HttpFoundation\Request;

    $request = new Request();
    handle_project_routing($request, 'prod', false);

Finally, a ``project_routing.yml`` file would be placed in the ``app/config``
directory with the following basic format::

    homepage:
        pattern:  /
        defaults: { _app: v2 }
    blog_show:
        pattern:  /blog/:slug
        defaults: { _app: v2 }
