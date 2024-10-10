<?php

declare(strict_types=1);

namespace GeorgRinger\ContainerModifyFields\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProvider\AbstractItemProvider;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\ArrayUtility;

class Modify extends AbstractItemProvider implements FormDataProviderInterface
{
    /**
     * Resolve select items
     *
     * @param array $result
     * @return array
     * @throws \UnexpectedValueException
     */
    public function addData(array $result)
    {
        if ($result['tableName'] !== 'tt_content') {
            return $result;
        }

        $parentContainerRecordId = (int)($result['databaseRow']['tx_container_parent'][0] ?? 0);
        if ($parentContainerRecordId === 0) {
            return $result;
        }

        $parentContainerRow = BackendUtility::getRecord('tt_content', $parentContainerRecordId);
        if (!$parentContainerRow) {
            return $result;
        }


        $overrideConfiguration = $result['pageTsConfig']['TCEFORM.']['tt_content.']['container.'][$parentContainerRow['CType'] . '.'] ?? [];
        if (empty($overrideConfiguration)) {
            return $result;
        }

        $colPos = $result['databaseRow']['colPos'][0];

        $configurationOfCurrentColpos = $overrideConfiguration[$colPos . '.'] ?? [];
        $configurationOfAllColpos = $overrideConfiguration['_all.'] ?? [];
        ArrayUtility::mergeRecursiveWithOverrule($configurationOfAllColpos, $configurationOfCurrentColpos);


        $inlineCtype = $result['databaseRow']['CType'][0] ?? '';
        $configPerCtype = $configurationOfAllColpos[$inlineCtype . '.'] ?? [];
        $configPerCtypeAll = $configurationOfAllColpos['_all.'] ?? [];
        ArrayUtility::mergeRecursiveWithOverrule($configPerCtypeAll, $configPerCtype);

        if (empty($configPerCtypeAll)) {
            return $result;
        }

        $this->overlayConfiguration($result, $configPerCtypeAll);
        return $result;
    }

    protected function overlayConfiguration(array &$result, array $configuration): void
    {
        foreach ($configuration as $fieldName => $fieldConfiguration) {
            $fieldName = rtrim($fieldName, '.');
            if (!isset($result['processedTca']['columns'][$fieldName])) {
                continue;
            }

            if ((int)($fieldConfiguration['disabled'] ?? false) === 1) {
                unset($result['processedTca']['columns'][$fieldName]);
            }
            if ((int)($fieldConfiguration['required'] ?? false) === 1) {
                $eval = $result['processedTca']['columns'][$fieldName]['config']['eval'] ?? '';
                $eval .= ',required';
                $result['processedTca']['columns'][$fieldName]['config']['eval'] = $eval;
            }
            if (isset($fieldConfiguration['fixedItemValue']) &&
                array_key_exists('items', $result['processedTca']['columns'][$fieldName]['config'])) {
                $items =  $result['processedTca']['columns'][$fieldName]['config']['items'];
                foreach ($items as $key => $item) {
                    if ($item[1] != $fieldConfiguration['fixedItemValue']) {
                        unset($result['processedTca']['columns'][$fieldName]['config']['items'][$key]);
                    }
                }
            }
            if (array_key_exists('maxitems', $fieldConfiguration)) {
                $result['processedTca']['columns'][$fieldName]['config']['maxitems'] = max(0, (int)$fieldConfiguration['maxitems']);
            }
            if (array_key_exists('minitems', $fieldConfiguration)) {
                $result['processedTca']['columns'][$fieldName]['config']['minitems'] = max(0, (int)$fieldConfiguration['minitems']);
            }
            if (array_key_exists('max', $fieldConfiguration)) {
                $result['processedTca']['columns'][$fieldName]['config']['max'] = max(0, (int)$fieldConfiguration['max']);
            }
            if (array_key_exists('size', $fieldConfiguration)) {
                $result['processedTca']['columns'][$fieldName]['config']['size'] = max(1, (int)$fieldConfiguration['size']);
            }
            if (array_key_exists('readOnly', $fieldConfiguration)) {
                $result['processedTca']['columns'][$fieldName]['config']['readOnly'] = (bool)$fieldConfiguration['readOnly'];
            }
            if (array_key_exists('enableCopyToClipboard', $fieldConfiguration)) {
                $result['processedTca']['columns'][$fieldName]['config']['enableCopyToClipboard'] = (bool)$fieldConfiguration['enableCopyToClipboard'];
            }
            if (array_key_exists('cols', $fieldConfiguration)) {
                $result['processedTca']['columns'][$fieldName]['config']['cols'] = max(1, (int)$fieldConfiguration['cols']);
            }
            if (array_key_exists('rows', $fieldConfiguration)) {
                $result['processedTca']['columns'][$fieldName]['config']['rows'] = max(1, (int)$fieldConfiguration['rows']);
            }
            if (array_key_exists('wrap', $fieldConfiguration)) {
                $result['processedTca']['columns'][$fieldName]['config']['wrap'] = (string)$fieldConfiguration['wrap'];
            }
        }
    }
}
