<?php

final class PhabricatorMarkupCache extends PhabricatorCacheDAO {

  protected $cacheKey;
  protected $cacheData;
  protected $metadata;

  public function getConfiguration() {
    return array(
      self::CONFIG_SERIALIZATION => array(
        'cacheData' => self::SERIALIZATION_PHP,
        'metadata'  => self::SERIALIZATION_JSON,
      ),
      self::CONFIG_BINARY => array(
        'cacheData' => true,
      ),
      self::CONFIG_COLUMN_SCHEMA => array(
        'cacheKey' => 'text128',
      ),
      self::CONFIG_KEY_SCHEMA => array(
        'cacheKey' => array(
          'columns' => array('cacheKey'),
        ),
      ),
    ) + parent::getConfiguration();
  }

}
