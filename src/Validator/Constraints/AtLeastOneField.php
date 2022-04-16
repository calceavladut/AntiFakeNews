<?php

use Symfony\Component\Validator\Constraint;
/**
 * Class AtLeastOneField
 *
 * @author Maria Nedelcu <maria.nedelcu@zitec.com>
 * @copyright Copyright (c) Zitec COM
 */
class AtLeastOneField extends Constraint
{
    /**
     * @var string
     */
    public $path;

    /**
     * AlreadyInDb constructor.
     * @param array|null $options
     */
    public function __construct(?array $options = null)
    {
        parent::__construct($options);

        if (count($options) > 0) {
            $this->path    = $options['path'];
        }
    }
}
