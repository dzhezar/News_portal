<?php

namespace App\Service\Home;

use App\Category\CategoriesCollection;
use App\Dto\PartnerPost;
use App\Post\PartnerPostsCollection;
use App\Post\PostMapper;
use App\Post\PostsCollection;
use App\Repository\Category\CategoryRepositoryInterface;
use App\Repository\Post\PostRepositoryInterface;
use GuzzleHttp\Client;

/**
 * @author Vladimir Kuprienko <vldmr.kuprienko@gmail.com>
 */
final class HomePageService implements HomePageServiceInterface
{
    private $categoryRepository;
    private $postRepository;

    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        PostRepositoryInterface $postRepository
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->postRepository = $postRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getPosts(): PostsCollection
    {
        $posts = $this->postRepository->findAllWithCategories();
        $collection = new PostsCollection();
        $dataMapper = new PostMapper();

        foreach ($posts as $post) {
            $collection->addPost($dataMapper->entityToDto($post));
        }

        return $collection;
    }
    /**
     * {@inheritdoc}
     */

    public function getCategories(): CategoriesCollection
    {
        $categories = $this->categoryRepository->findAllIsPublished();

        return new CategoriesCollection($categories);
    }

    public function getPartnersPosts(): PartnerPostsCollection
    {
        $client = new Client();
        $response = $client->request('GET', 'https://habr.com/rss/best/daily');

        $feed = (string) $response->getBody();

        $xml = new \SimpleXMLElement($feed);
        $collection = new PartnerPostsCollection();
        $counter = 0;

        foreach ($xml->xpath('//item') as $post) {
            if ($counter === 3) {
                break;
            }

            $collection->addPost(new PartnerPost(
                (string) $post->title,
                (string) $post->description,
                $post->link
            ));

            ++$counter;
        }

        return $collection;
    }
}
