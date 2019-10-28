<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\BlogGraphQl\Model\UrlResolver;

use Magefan\Blog\Model\Url;
use Magefan\Blog\Model\Post;
use Magefan\Blog\Model\Category;
use Magento\Store\Model\StoreManagerInterface;
use Magefan\Blog\Model\Tag;
use Magefan\Blog\Api\AuthorInterface;
use Magefan\Blog\Model\Search;

/**
 * Blog Controller Router
 */
class Router
{
    /**
     * @var Url
     */
    protected $url;

    /**
     * @var Post
     */
    protected $post;

    /**
     * @var Category
     */
    protected $category;

    protected $blogObjectStoreId;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var array;
     */
    protected $ids;

    /**
     * @var Tag
     */
    protected $tag;

    /**
     * @var AuthorInterface
     */
    protected $author;

    /**
     * Router constructor.
     * @param Url $url
     * @param Post $post
     * @param Category $category
     * @param StoreManagerInterface $storeManager
     * @param Tag $tag
     * @param AuthorInterface $author
     */
    public function __construct(
        Url $url,
        Post $post,
        Category $category,
        StoreManagerInterface $storeManager,
        Tag $tag,
        AuthorInterface $author
    ) {
        $this->url = $url;
        $this->post = $post;
        $this->category = $category;
        $this->storeManager = $storeManager;
        $this->tag = $tag;
        $this->author = $author;
    }

    /**
     * @return mixed
     */
    protected function getBlogUrl()
    {
        return $this->url;
    }

    /**
     * @param $identifier
     * @return array|void|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getBlogPage($identifier)
    {
        $postId = $categoryId = $tagId = $authorId = null;

        $pathInfo = explode('/', $identifier);
        $blogRoute = $this->getBlogUrl()->getRoute();

        if ($pathInfo[0] != $blogRoute) {
            return;
        }

        unset($pathInfo[0]);

        $blogUrl = $this->getBlogUrl();

        if (!count($pathInfo)) {
            //do nothing
        } elseif ($pathInfo[1] == $this->getBlogUrl()->getRoute($blogUrl::CONTROLLER_RSS)) {
            if (!isset($pathInfo[2]) || in_array($pathInfo[2], ['index', 'feed'])) {
                //do nothing
            }
        } elseif ($pathInfo[1] == $this->getBlogUrl()->getRoute($blogUrl::CONTROLLER_SEARCH)
            && !empty($pathInfo[2])
        ) {
            //do nothing
        } else {
            $controllerName = null;
            if ($blogUrl::PERMALINK_TYPE_DEFAULT == $this->getBlogUrl()->getPermalinkType()) {
                $controllerName = $this->getBlogUrl()->getControllerName($pathInfo[1]);
                unset($pathInfo[1]);
            }

            $pathInfo = array_values($pathInfo);
            $pathInfoCount = count($pathInfo);

            if ($pathInfoCount == 1) {
                if ((!$controllerName || $controllerName == $blogUrl::CONTROLLER_ARCHIVE)
                    && $this->_isArchiveIdentifier($pathInfo[0])
                ) {
                    //do nothing
                } elseif ((!$controllerName || $controllerName == $blogUrl::CONTROLLER_POST)
                    && $postId = $this->_getPostId($pathInfo[0])
                ) {
                    //OK Have Post ID
                } elseif ((!$controllerName || $controllerName == $blogUrl::CONTROLLER_CATEGORY)
                    && $categoryId = $this->_getCategoryId($pathInfo[0])
                ) {
                    //OK Have Category ID
                } elseif ((!$controllerName || $controllerName == $blogUrl::CONTROLLER_TAG)
                    && $tagId = $this->_getTagId($pathInfo[0])
                ) {
                    //OK Have Tag ID
                } elseif ((!$controllerName || $controllerName == $blogUrl::CONTROLLER_AUTHOR)
                    && $authorId = $this->_getAuthorId($pathInfo[0])
                ) {
                    //OK Have Author ID
                }
            } elseif ($pathInfoCount > 1) {
                $postId = 0;
                $categoryId = 0;
                $tagId = 0;
                $authorId = 0;
                $first = true;
                $pathExist = true;

                for ($i = $pathInfoCount - 1; $i >= 0; $i--) {
                    if ((!$controllerName || $controllerName == $blogUrl::CONTROLLER_POST)
                        && $first
                        && ($postId = $this->_getPostId($pathInfo[$i]))
                    ) {
                        //we have postId
                    } elseif ((!$controllerName || !$first || $controllerName == $blogUrl::CONTROLLER_CATEGORY)
                        && ($cid = $this->_getCategoryId($pathInfo[$i], $first))
                    ) {
                        if (!$categoryId) {
                            $categoryId = $cid;
                        }
                    } elseif ((!$controllerName || !$first || $controllerName == $blogUrl::CONTROLLER_TAG)
                        && ($tid = $this->_getTagId($pathInfo[$i], $first))
                    ) {
                        if (!$tagId) {
                            $tagId = $tid;
                        }
                    } elseif ((!$controllerName || !$first || $controllerName == $blogUrl::CONTROLLER_AUTHOR)
                        && ($aid = $this->_getAuthorId($pathInfo[$i], $first))
                    ) {
                        if (!$authorId) {
                            $authorId = $aid;
                        }
                    } else {
                        $pathExist = false;
                        break;
                    }

                    if ($first) {
                        $first = false;
                    }
                }

                if ($pathExist) {
                    //do nothing
                } elseif ((!$controllerName || $controllerName == $blogUrl::CONTROLLER_POST)
                    && $postId = $this->_getPostId(implode('/', $pathInfo))
                ) {
                    //OK Have Post ID
                }
            }
        }

        if ($postId) {
            return [$postId, 1];
        }

        if ($categoryId) {
            return [$categoryId, 2];
        }

        if ($tagId) {
            return [$tagId, 3];
        }

        if ($authorId) {
            return [$authorId, 4];
        }

        if ($this->_isArchiveIdentifier($pathInfo[0])) {
            $identifier = explode('/', $identifier);
            return [end($identifier), 5];
        }

        return null;
    }

    /**
     * @param $identifier
     * @param bool $checkSuffix
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function _getPostId($identifier, $checkSuffix = true)
    {
        $blogUrl = $this->getBlogUrl();

        return $this->getObjectId(
            $this->post,
            $blogUrl::CONTROLLER_POST,
            $identifier,
            $checkSuffix
        );
    }

    /**
     * @param $identifier
     * @param bool $checkSuffix
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function _getCategoryId($identifier, $checkSuffix = true)
    {
        $blogUrl = $this->getBlogUrl();

        return $this->getObjectId(
            $this->category,
            $blogUrl::CONTROLLER_CATEGORY,
            $identifier,
            $checkSuffix
        );
    }

    /**
     * @param $identifier
     * @param bool $checkSuffix
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function _getTagId($identifier, $checkSuffix = true)
    {
        $blogUrl = $this->getBlogUrl();

        return $this->getObjectId(
            $this->tag,
            $blogUrl::CONTROLLER_TAG,
            $identifier,
            $checkSuffix
        );
    }

    /**
     * @param $identifier
     * @param bool $checkSuffix
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function _getAuthorId($identifier, $checkSuffix = true)
    {
        $blogUrl = $this->getBlogUrl();

        return $this->getObjectId(
            $this->author,
            $blogUrl::CONTROLLER_AUTHOR,
            $identifier,
            $checkSuffix
        );
    }

    /**
     * Detect archive identifier
     * @param $identifier
     * @return bool
     */
    protected function _isArchiveIdentifier($identifier)
    {
        $info = explode('-', $identifier);
        return count($info) == 2
            && strlen($info[0]) == 4
            && strlen($info[1]) == 2
            && is_numeric($info[0])
            && is_numeric($info[1]);
    }

    /**
     * @param $object
     * @param $controllerName
     * @param $identifier
     * @param $checkSuffix
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getObjectId($object, $controllerName, $identifier, $checkSuffix)
    {
        $storeId = $this->storeManager->getStore()->getId();

        $stores = [];
        foreach ($this->storeManager->getStores() as $value) {
            $stores[] = $value->getId();
        }

        foreach ($stores as $_storeId) {
            if ($_storeId == $storeId) {
                continue;
            }

            $this->blogObjectStoreId = $_storeId;
        }

        $key = $this->blogObjectStoreId
        . '_' . $controllerName
        . '-' .$identifier
        . ($checkSuffix ? '-checksuffix' : '');

        if (!isset($this->ids[$key])) {
            $suffix = $this->getBlogUrl()->getUrlSufix($controllerName);
            $trimmedIdentifier = $this->getBlogUrl()->trimSufix($identifier, $suffix);

            if ($checkSuffix && $suffix && $trimmedIdentifier == $identifier) { //if url without suffix
                $this->ids[$key] = 0;
            } else {
                //$object = $factory->create();

                $this->ids[$key] = $object->checkIdentifier(
                    $trimmedIdentifier,
                    $this->blogObjectStoreId //$this->storeManager->getStore()->getId()
                );
            }
        }

        return $this->ids[$key];
    }
}