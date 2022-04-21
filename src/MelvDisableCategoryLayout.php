<?php declare(strict_types=1);

namespace Melv\DisableCategoryLayout;

use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\CustomField\CustomFieldTypes;

class MelvDisableCategoryLayout extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);

        $this->createCustomFields();
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        $this->deleteCustomFields();
    }

    private function createCustomFields()
    {
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
        $customFieldSetUuid = Uuid::randomHex();

        $customFieldSetRepository->upsert([
            [
                'id' => $customFieldSetUuid,
                'name' => 'melv_category_layout',
                'global' => true,
                'config' => [
                    'label' => [
                        'en-GB' => 'Layout override'
                    ]
                ],
                'customFields' => [
                    [
                        'id' => Uuid::randomHex(),
                        'name' => 'melv_category_layout_enable_override',
                        'type'   => CustomFieldTypes::BOOL,
                        'config' => [
                            'type'                => 'checkbox',
                            'label'               => ['en-GB' => 'Enable'],
                            'componentName'       => 'sw-field',
                            'customFieldType'     => 'checkbox',
                            'customFieldPosition' => 1
                        ]
                    ],
                ],
                'relations' => [
                    [
                        'id' => $customFieldSetUuid,
                        'entityName' => $this->container->get(CategoryDefinition::class)->getEntityName()
                    ],
                ]
            ]
        ], Context::createDefaultContext());
    }

    private function deleteCustomFields()
    {
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        $entityIds = $customFieldSetRepository->search(
            (new Criteria())->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, [
                new EqualsFilter('name', 'melv_category_layout'),
            ])),
            Context::createDefaultContext()
        )->getEntities()->getIds();

        if (count($entityIds) < 1) {
            return;
        }

        $entityIds = array_map(function ($element) {
            return ['id' => $element];
        }, array_values($entityIds));

        $customFieldSetRepository->delete(
            $entityIds,
            Context::createDefaultContext()
        );
    }
}
