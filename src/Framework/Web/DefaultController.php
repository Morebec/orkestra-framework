<?php


namespace Morebec\Orkestra\OrkestraFramework\Framework\Web;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    /**
     * @Route(name="orkestra.framework.default_controller", path="/")
     * @param Request $request
     * @return Response
     */
    public function __invoke(Request $request): Response
    {
        return $this->json(['hello' => uniqid( 'world_')]);
    }
}