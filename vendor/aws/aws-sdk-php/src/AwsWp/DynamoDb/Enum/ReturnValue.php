<?php
/**
 * Copyright 2010-2013 Amazon.com, Inc. or its affiliates. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * A copy of the License is located at
 *
 * http://aws.amazon.com/apache2.0
 *
 * or in the "license" file accompanying this file. This file is distributed
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */

namespace AwsWp\DynamoDb\Enum;

use AwsWp\Common\Enum;

/**
 * Contains enumerable ReturnValue values
 */
class ReturnValue extends Enum
{
    const NONE = 'NONE';
    const ALL_OLD = 'ALL_OLD';
    const UPDATED_OLD = 'UPDATED_OLD';
    const ALL_NEW = 'ALL_NEW';
    const UPDATED_NEW = 'UPDATED_NEW';
}
