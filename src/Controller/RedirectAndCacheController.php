<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RedirectAndCacheController extends BaseController
{
    public function redirectAction(
        Request $request,
        $route,
        UrlGeneratorInterface $router,
        $permanent = false,
        $ignoreAttributes = false
    ) {
        if ('' == $route) {
            throw new HttpException($permanent ? 410 : 404);
        }

        $attributes = array();
        if (false === $ignoreAttributes || is_array($ignoreAttributes)) {
            $attributes = $request->attributes->get('_route_params');
            unset($attributes['route'], $attributes['permanent'], $attributes['ignoreAttributes']);
            if ($ignoreAttributes) {
                $attributes = array_diff_key($attributes, array_flip($ignoreAttributes));
            }
        }

        if ($permanent) {
            $statusCode = Response::HTTP_MOVED_PERMANENTLY;
        } else {
            /**
             * If we change it to 307 we have to re-test all the shit, but we use this constant now
             * to keep track of the fact that 302 changed its meaning
             */
            $statusCode = Response::HTTP_FOUND;
        }
        $response = new RedirectResponse(
            $router->generate($route, $attributes, UrlGeneratorInterface::ABSOLUTE_URL),
            $statusCode
        );
        $response->setPublic()->setMaxAge(3600);
        
        return $response;
    }
}
