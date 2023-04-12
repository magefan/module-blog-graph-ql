<?php
declare(strict_types=1);

namespace Magefan\BlogGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Query\Resolver\IdentityInterface;
use Magefan\Blog\Model\Post as PostEntity;
use Magefan\Blog\Model\Category;
use Magefan\Blog\Model\Tag;
use Magefan\Blog\Model\Comment;

/**
 * Get identities from resolved data
 */
class BlogIdentity implements IdentityInterface
{
    private $cacheTagPost = PostEntity::CACHE_TAG;
    private $cacheTagPostCategory = Category::CACHE_TAG;
    private $cacheTagPostTag = Tag::CACHE_TAG;
    private $cacheTagPostComment = Comment::CACHE_TAG;

    /**
     * Get identity tags from resolved data
     *
     * @param array $resolvedData
     * @return string[]
     */
    public function getIdentities(array $resolvedData): array
    {
        $ids = [];
        $categories = $resolvedData['categories'] ?? [];
        foreach ($categories as $category) {
            $ids[] = sprintf('%s_%s', $this->cacheTagPostCategory, $category);
        }
        if (!empty($categories)) {
            array_unshift($ids, $this->cacheTagPostCategory);
        }

        $tags = $resolvedData['tags'] ?? [];
        foreach ($tags as $tag) {
            $ids[] = sprintf('%s_%s', $this->cacheTagPostTag, $tag);
        }
        if (!empty($tags)) {
            array_unshift($ids, $this->cacheTagPostTag);
        }

        $postId = $resolvedData['post_id'] ?? '';
        $ids[] = sprintf('%s_%s', $this->cacheTagPost, $postId);

        if (!empty($ids)) {
            $ids[] = $this->cacheTagPost;
        }

        $postId = $resolvedData['author_id'] ?? '';
        $ids[] = sprintf('%s_%s', $this->cacheTagPost, $postId);

        if (!empty($ids)) {
            $ids[] = $this->cacheTagPost;
        }

        $items = $resolvedData['items'] ?? [];
        foreach ($items as $item) {
            if ($item['tag_id']) {
                $ids[] = sprintf('%s_%s', $this->cacheTagPostTag, $item['tag_id']);
            } elseif ($item['category_id']) {
                $ids[] = sprintf('%s_%s', $this->cacheTagPostCategory, $item['category_id']);
            } elseif ($item['post_id']) {
                $ids[] = sprintf('%s_%s', $this->cacheTagPost, $item['post_id']);
            } elseif ($item['comment_id']) {
                $ids[] = sprintf('%s_%s', $this->cacheTagPostComment, $item['comment_id']);
            }
        }
        if (!empty($ids)) {
            $ids[] = $this->cacheTagPost;
        }
        return $ids;
    }
}
