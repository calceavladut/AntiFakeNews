<?php

use Symfony\Component\Validator\Constraint;
use \Symfony\Component\Validator\ConstraintValidator;
/**
 * Class AtLeastOneFieldValidator
 *
 * @author Maria Nedelcu <maria.nedelcu@zitec.com>
 * @copyright Copyright (c) Zitec COM
 */
class AtLeastOneFieldValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$value->getUrl() && !$value->getText()) {
            $this->context
                ->buildViolation('Please give us something to work with...')
                ->atPath($constraint->path)
                ->addViolation();
        }
    }
}
