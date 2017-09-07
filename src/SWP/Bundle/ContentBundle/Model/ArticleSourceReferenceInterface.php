<?php

declare(strict_types=1);

/*
 * This file is part of the Superdesk Web Publisher Content Bundle.
 *
 * Copyright 2016 Sourcefabric z.ú. and contributors.
 *
 * For the full copyright and license information, please see the
 * AUTHORS and LICENSE files distributed with this source code.
 *
 * @copyright 2016 Sourcefabric z.ú
 * @license http://www.superdesk.org/license
 */

namespace SWP\Bundle\ContentBundle\Model;

use SWP\Component\Storage\Model\PersistableInterface;

interface ArticleSourceReferenceInterface extends PersistableInterface
{
    /**
     * @return ArticleInterface
     */
    public function getArticle(): ArticleInterface;

    /**
     * @param ArticleInterface $article
     */
    public function setArticle(ArticleInterface $article);

    /**
     * @return ArticleSource
     */
    public function getArticleSource(): ArticleSourceInterface;

    /**
     * @param ArticleSource $articleSource
     */
    public function setArticleSource(ArticleSourceInterface $articleSource);
}
