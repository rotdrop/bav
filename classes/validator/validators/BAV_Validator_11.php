<?php

/**
 * Implements 11
 *
 * Copyright (C) 2006  Markus Malkusch <markus@malkusch.de>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
class BAV_Validator_11 extends BAV_Validator_06
{

    public function __construct(BAV_Bank $bank)
    {
        parent::__construct($bank);

        $this->setWeights(array(2, 3, 4, 5, 6, 7, 8, 9, 10));
    }

    protected function getResult()
    {
        $result = 11 - $this->accumulator % 11;
        switch ($result) {
            case 11:
                $result = 0;
                break;
            case 10:
                $result = 9;
                break;
        }
        return (string)$result === $this->getCheckNumber();
    }
}