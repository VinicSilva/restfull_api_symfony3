<?php
/**
 * Created by PhpStorm.
 * User: marcosvinicius
 * Date: 08/06/18
 * Time: 11:06
 */

namespace AppBundle\Controller;

use AppBundle\Entity\EntityMerger;
use AppBundle\Entity\Movie;
use AppBundle\Entity\Role;
use AppBundle\Exception\ValidationException;
use FOS\RestBundle\Controller\ControllerTrait;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * @Security("is_anonymous() or is_authenticated()")
 */
class MoviesController extends AbstractController
{
    use ControllerTrait;

    /*
     * @var EntityMerger
     */
    private $entityMerger;

    /*
     * @param EntityMerger $entityMerger
     */
    public function __construct(EntityMerger $entityMerger)
    {
        $this->entityMerger = $entityMerger;
    }

    /**
     * @Rest\View()
     */
    public function getMoviesAction()
    {
        $movies  = $this->getDoctrine()->getRepository('AppBundle:Movie')->findAll();

        return $movies;
    }

    /**
     * @Rest\View(statusCode=201)
     * @ParamConverter("movie", converter="fos_rest.request_body")
     * @Rest\NoRoute()
     */
    public function postMoviesAction(Movie $movie, ConstraintViolationListInterface $validationErrors)
    {
        if(count($validationErrors) > 0){
            throw new ValidationException($validationErrors);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($movie);
        $em->flush();

        return $movie;
    }

    /**
     * @Rest\View()
     */
    public function deleteMoviesAction(Movie $movie)
    {
        if($movie == null){
            return $this->view(null, 404);
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($movie);
        $em->flush();
    }

    /**
     * @Rest\View()
     */
    public function getMovieAction(Movie $movie)
    {
        if($movie == null){
            return $this->view(null, 404);
        }

        return $movie;
    }

    /**
     * @Rest\View()
     */
    public function getMovieRolesAction(Movie $movie)
    {
        return $movie->getRoles();
    }

    /**
     * @Rest\View(statusCode=201)
     * @ParamConverter("role", converter="fos_rest.request_body", options={"deserializationContext"={"groups"={"Deserialize"}}})
     * @Rest\NoRoute()
     */
    public function postMovieRolesAction(Movie $movie, Role $role, ConstraintViolationListInterface $validationErrors)
    {
        if(count($validationErrors) > 0){
            throw new ValidationException($validationErrors);
        }

        $role->setMovie($movie);
        $em = $this->getDoctrine()->getManager();
        $em->persist($role);

        $movie->getRoles()->add($role);
        $em->persist($movie);
        $em->flush();

        return $role;
    }

    /**
     * @Rest\NoRoute()
     * @ParamConverter("modifiedMovie", converter="fos_rest.request_body",
     *      options={"validator" = {"groups" = {"Patch"}}}
     * )
     * @Security("is_authenticated()")
     */
    public function patchMovieAction(?Movie $movie, Movie $modifiedMovie, ConstraintViolationListInterface $validationErrors)
    {
        if(null === $movie){
            return $this->view(null, 404);
        }

        if(count($validationErrors) > 0){
            throw new ValidationException($validationErrors);
        }

        //Merge Entities
        $this->entityMerger->merge($movie, $modifiedMovie);

        //Persist
        $em = $this->getDoctrine()->getManager();
        $em->persist($movie);
        $em->flush();
        //Return

        return $movie;
    }
}