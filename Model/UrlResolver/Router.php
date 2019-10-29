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
use Magento\Framework\Module\ModuleListInterface;

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

    /**
     * @var int
     */
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
     * @var ModuleListInterface
     */
    protected $moduleList;

    /**
     * Router constructor.
     * @param Url $url
     * @param Post $post
     * @param Category $category
     * @param StoreManagerInterface $storeManager
     * @param Tag $tag
     * @param AuthorInterface $author
     * @param ModuleListInterface $moduleList
     */
    public function __construct(
        Url $url,
        Post $post,
        Category $category,
        StoreManagerInterface $storeManager,
        Tag $tag,
        AuthorInterface $author,
        ModuleListInterface $moduleList
    ) {
        $this->url = $url;
        $this->post = $post;
        $this->category = $category;
        $this->storeManager = $storeManager;
        $this->tag = $tag;
        $this->author = $author;
        $this->moduleList = $moduleList;
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
        $postId = $categoryId = $tagId = $authorId = $archiveId = $searchId = null;
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
                    && $archiveId = $this->_isArchiveIdentifier($pathInfo[0])
                ) {
                    //do nothing
                } elseif ((!$controllerName || $controllerName == $blogUrl::CONTROLLER_SEARCH)
                    && $searchId = $this->_isSearchIdentifier($pathInfo[0])
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

        if ($archiveId) {
            $identifier = explode('/', $identifier);
            return [end($identifier), 5];
        }

        if ($searchId) {
            $identifier = explode('/', $identifier);
            return [end($identifier), 6];
        }

        return null;
    }

    /**
     * @param $identifier
     * @return array
     */
    public function getBlogPlusPage($identifier)
    {
        if ($this->moduleList->has('Magefan_BlogPlus')) {
            $identifierLen = strlen($identifier);
            $basePath = trim($this->getBlogUrl()->getBasePath(), '/');

            if ($identifier != $basePath) {
                $schemas = $this->getBlogUrl()->getPathChemas();

                foreach ($schemas as $controllerName => $schema) {
                    $schema = trim($schema, '/');
                    $startVar = strpos($schema, '{');
                    $endVar = strrpos($schema, '}');

                    if (false === $startVar || false === $endVar) {
                        continue;
                    }

                    if (substr($schema, 0, $startVar) != substr($identifier, 0, $startVar)) {
                        continue;
                    }

                    $endVar++;

                    if (substr($schema, $endVar)
                        != substr($identifier, $identifierLen - (strlen($schema) - $endVar))) {
                        continue;
                    }

                    $subSchema = substr($schema, $startVar, $endVar - $startVar);
                    $subIdentifier = substr(
                        $identifier,
                        $startVar,
                        $identifierLen - (strlen($schema) - $endVar) - $startVar
                    );
                    $pathInfo = explode('/', $subIdentifier);
                    $subSchema = explode('/', $subSchema);

                    if (($subSchema[0] == '{{blog_route}}') && (strpos($subIdentifier, $basePath) === false)) {
                        continue;
                    }

                    if ('{' != $subSchema[0]{0} && $subSchema[0] != $pathInfo[0]) {
                        continue;
                    }

                    if ($subSchema[count($subSchema) - 1] == '{{url_key}}') {
                        switch ($controllerName) {
                            case 'post':
                            case 'category':
                            case 'tag':
                            case 'author':
                                $method = '_get' . ucfirst($controllerName) . 'Id';
                                $id = $this->$method($pathInfo[count($pathInfo) - 1]);

                                if ($id) {
                                    $model = $this->$controllerName->load($id);

                                    if ($model->getId()) {
                                        $path = $this->getBlogUrl()->getUrlPath($model, $controllerName);
                                        $path = trim($path, '/');

                                        if ($path == $identifier) {
                                            switch ($controllerName) {
                                                case 'post':
                                                    return [$id, 1];
                                                case 'category':
                                                    return [$id, 2];
                                                case 'tag':
                                                    return [$id, 3];
                                                case 'author':
                                                    return [$id, 4];
                                            }
                                        }
                                    }
                                }
                            break;
                            case 'archive':
                                $date = $pathInfo[count($pathInfo) - 1];

                                if ($this->_isArchiveIdentifier($date)) {
                                    $path = $this->getBlogUrl()->getUrlPath($date, $controllerName);
                                    $path = trim($path, '/');

                                    if ($path == $identifier) {
                                        $path = explode('/', $path);
                                        return [end($path), 5];
                                    }
                                }
                            break;
                            case 'search':
                                $q = '';
                                for ($x = 1; $x <=4; $x++) {
                                    if (!isset($pathInfo[count($pathInfo) - $x])) {
                                        break;
                                    }
                                    $q = $pathInfo[count($pathInfo) - $x] . ($q ? '/' : '') . $q;
                                    $path = $this->getBlogUrl()->getUrlPath($q, $controllerName);
                                    $path = trim($path, '/');

                                    if ($path == $identifier) {
                                        $path = explode('/', $path);
                                        return [end($path), 6];
                                    }
                                }
                            default:
                                /* do nothing */
                        }
                    }
                }
            }
        }
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
     * @param $identifier
     * @return bool
     */
    protected function _isSearchIdentifier($identifier)
    {
        return strlen($identifier) > 0;
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