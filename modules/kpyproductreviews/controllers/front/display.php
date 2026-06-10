<?php

class KpyProductReviewsDisplayModuleFrontController extends ModuleFrontController
{
    private Product $product;

    public function init()
    {
        parent::init();

        if (!$this->isProductLoaded()) {
            Tools::redirect($this->context->link->getPageLink('index'));
        }
    }

    private function isProductLoaded(): bool
    {
        $sqlGetProductIdByRewrite = "select pl.id_product
            from " . _DB_PREFIX_ . "product_lang pl
            where pl.link_rewrite = '" . Tools::getValue('product_rewrite') . "'
              and pl.id_lang = {$this->context->language->id}
              and pl.id_shop = {$this->context->shop->id}
              and EXISTS (SELECT 1 
                    FROM " . _DB_PREFIX_ . "product_shop ps 
                    WHERE ps.id_product = pl.id_product 
                        and pl.id_shop = ps.id_shop 
                        and ps.visibility = 'both'
                        and ps.active = 1)";

        $this->product = new Product(
            (int)Db::getInstance()->getValue($sqlGetProductIdByRewrite),
            false,
            $this->context->language->id,
            $this->context->shop->id
        );


        return Validate::isLoadedObject($this->product);
    }

    public function initContent(): void
    {
        parent::initContent();

        $this->context->controller->page_name = 'kpyproductreviews';

        $productPresenterFactory = new ProductPresenterFactory($this->context);
        $productPresenter = $productPresenterFactory->getPresenter();

        $product = $this->objectPresenter->present($this->product);
        $product['out_of_stock'] = (int)$this->product->out_of_stock;

        $product_full = Product::getProductProperties(
            $this->context->language->id,
            $product,
            $this->context
        );

        /** @var \PrestaShop\Module\ProductComment\Repository\ProductCommentRepository $commentRepository */
        $commentRepository = $this->get('product_comment_repository');

        $commentsNumber = $commentRepository->getCommentsNumber($this->product->id, (bool)Configuration::get('PRODUCT_COMMENTS_MODERATE'));

        $commentsByGrade = array_reduce(
            $commentRepository->getNumberCommentsByGrade($this->product->id),
            static function ($carry, $row) {
                $carry[(int)$row['grade']] = $row['comments'];
                return $carry;
            }
        );

        $commentsByGradeNb = [];
        for ($i = 5; $i > 0; $i--) {
            $commentsByGradeNb[] = [
                'grade' => $i,
                'nb_comments' => $commentsByGrade[$i] ?? 0,
                'width' => $commentsNumber > 0 ? round(($commentsByGrade[$i] ?? 0) * 100 / $commentsNumber, 2) : 0
            ];
        }

        $presentedProduct = $productPresenter->present(
            $productPresenterFactory->getPresentationSettings(),
            $product_full,
            $this->context->language
        );

        $this->context->smarty->assign([
            'product' => $presentedProduct,
            'average_grade' => $commentRepository->getAverageGrade($this->product->id, (bool)Configuration::get('PRODUCT_COMMENTS_RATE')),
            'comments_number' => $commentsNumber,
            'nb_grade' => $commentsByGradeNb,
            'comments' => $this->getComments(),
            'usefulness_enabled' => Configuration::get('PRODUCT_COMMENTS_USEFULNESS'),
            'post_allowed' => $commentRepository->isPostAllowed($this->product->id, $this->context->customer->id, $this->context->cookie->id_guest),
            'comment_modal' => Module::getInstanceByName('productcomments')
                ->renderProductCommentModal($presentedProduct),
        ]);

        if (!empty($vet_review = $this->getVetReview($this->product->id, $this->context->language->id))) {
            $this->context->smarty->assign([
                'vet_review' => $vet_review,
            ]);
        }

        $this->setTemplate('module:' . $this->module->name . '/views/templates/front/display.tpl');
    }

    public function getComments(): array
    {
        $sql = "select pc.id_product_comment, c.lastname, c.firstname, pc.content, pc.date_add, pc.grade, pc.customer_name,
                (select count(*)
                    from " . _DB_PREFIX_ . "product_comment_usefulness pcu
                    where usefulness = 1
                      and pcu.id_product_comment = pc.id_product_comment) as usefulness1,
                (select count(*)
                    from " . _DB_PREFIX_ . "product_comment_usefulness pcu
                    where usefulness = 0
                      and pcu.id_product_comment = pc.id_product_comment) as usefulness0
            from " . _DB_PREFIX_ . "product_comment pc
            left join " . _DB_PREFIX_ . "customer c
                on c.id_customer = pc.id_customer
                    and c.id_shop = {$this->context->shop->id}
            where pc.id_product = {$this->product->id}
                and pc.validate = 1
                and pc.deleted = 0
            order by pc.date_add desc";

        return array_map(function (array $comment): array {
            return [
                'id' => $comment['id_product_comment'],
                'customer_name' => !empty($comment['customer_name'])
                    ? $comment['customer_name'] : $this->anonymizeName($comment['firstname'] . ' ' . $comment['lastname']),
                'grade' => $comment['grade'],
                'content' => $comment['content'],
                'date_add' => $comment['date_add'],
                'usefulness1' => $comment['usefulness1'],
                'usefulness0' => $comment['usefulness0'],
            ];
        }, Db::getInstance()->executeS($sql));
    }

    private function anonymizeName($name)
    {
        $parts = explode(' ', $name);
        $firstName = $parts[0];
        $lastName = count($parts) > 1 ? array_pop($parts) : '';
        $name = $firstName;
        if (!empty($lastName)) {
            $name .= ' ' . mb_substr($lastName, 0, 1, 'UTF-8') . '.';
        }

        return $name;
    }

    public function getTemplateVarPage(): array
    {
        $page = parent::getTemplateVarPage();

        $page['meta']['title'] = $this->trans('Reviews of %product_name%', [
            '%product_name%' => $this->product->name,
        ], 'Modules.Kpyproductreviews.Shop') . ' - ' . Configuration::get('PS_SHOP_NAME');

        $page['meta']['robots'] = 'index, follow';

        return $page;
    }

    public function getCanonicalURL(): string
    {
        return $this->context->link->getProductLink($this->product);
    }

    public function setMedia(): void
    {
        parent::setMedia();

        $this->registerStylesheet(
            'module-' . $this->module->name . '-fc-' . basename(__FILE__) . '-style',
            'modules/' . $this->module->name . '/views/css/display.css',
            [
                'media' => 'all',
                'priority' => 1000,
            ]
        );

        $this->registerJavascript(
            'modules/' . $this->module->name . '-fc-' . basename(__FILE__) . '-script',
            'modules/' . $this->module->name . '/views/js/display.js',
            [
                'position' => 'bottom',
                'priority' => 200,
            ]
        );

        $cssList = ['modules/productcomments/views/css/productcomments.css',];
        $jsList = [
            'modules/productcomments/views/js/post-comment.js',
            'modules/productcomments/views/js/list-comments.js',
            'modules/productcomments/views/js/scrolling.js',
        ];


        foreach ($cssList as $cssUrl) {
            $this->registerStylesheet(sha1($cssUrl), $cssUrl, ['media' => 'all', 'priority' => 200]);
        }
        foreach ($jsList as $jsUrl) {
            $this->registerJavascript(sha1($jsUrl), $jsUrl, ['position' => 'bottom', 'priority' => 200]);
        }
    }

    private function getVetReview(int $productId, int $idLang): array
    {
        return Db::getInstance()->getRow(
            "SELECT title, review 
                FROM " . _DB_PREFIX_ . "kpy_vet_review 
                WHERE id_product=$productId 
                    AND id_lang=$idLang"
        ) ?: [];
    }
}