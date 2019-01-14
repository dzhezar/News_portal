<?php

namespace App\Controller\Api;

use App\Entity\Post;
use App\Repository\Category\CategoryRepository;
use App\Repository\Post\PostRepository;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class PostController extends AbstractFOSRestController
{
    /**
     * @Rest\Post("/post")
     */
    public function postPost(Request $request, CategoryRepository $categoryRepository)
    {
        $category = $categoryRepository->find($request->request->get('categoryId'));

        if (empty($category)) {
            throw new BadRequestHttpException();
        }

        $post = new Post();
        $post->setTitle($request->request->get('title'))
            ->setBody($request->request->get('body'))
            ->setCategory($category)
            ;

        $post->setSlug(
            \strtolower(\str_replace($request->request->get('title'), ' ', '-'))
        );

        $em = $this->getDoctrine()->getManager();
        $em->persist($post);
        $em->flush();

        $response = [
            'id' => $post->getId(),
            'title' => $post->getTitle(),
            'body' => $post->getBody(),
        ];

        return $this->view($response, Response::HTTP_CREATED);
    }

    /**
     * @Rest\Get("/post/{id}")
     */
    public function getPost(int $id, PostRepository $postRepository)
    {
        $post = $this->findPost($id, $postRepository);

        $response = [
            'id' => $post->getId(),
            'title' => $post->getTitle(),
            'body' => $post->getBody(),
        ];

        return $this->view($response, Response::HTTP_OK);
    }

    /**
     * @Rest\Get("/posts")
     */

    public function getAllPosts(PostRepository $postRepository)
    {
        $posts = $postRepository->findAll();

        foreach ($posts as $post){
            $response[] = [
                'id' => $post->getId(),
                'title' => $post->getTitle(),
                'body' => $post->getBody(),
            ];
        }
        return $this->view($response, Response::HTTP_OK);
    }

    /**
     * @Rest\Delete("/post/{id}")
     */
    public function deletePost(int $id, PostRepository $postRepository)
    {
        $post = $this->findPost($id, $postRepository);
        $em = $this->getDoctrine()->getManager();

        $em->remove($post);
        $em->flush();

        return $this->view([], Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\Patch("/post/{id}")
     */
    public function patchPost(int $id, PostRepository $postRepository, Request $request)
    {
        $post = $this->findPost($id, $postRepository);

        if ($title = $request->request->get('title')) {
            $post->setTitle($title);
        }

        if ($body = $request->request->get('body')) {
            $post->setBody($body);
        }

        $em = $this->getDoctrine()->getManager();
        $em->flush();

        $response = [
            'id' => $post->getId(),
            'title' => $post->getTitle(),
            'body' => $post->getBody(),
        ];

        return $this->view($response, Response::HTTP_OK);
    }

    private function findPost(int $id, PostRepository $postRepository)
    {
        $post = $postRepository->find($id);

        if (empty($post)) {
            throw $this->createNotFoundException();
        }

        return $post;
    }
}
