<?php
/**
 * Copyright (c) 2011-2013 Antoine Hedgecock.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the names of the copyright holders nor the names of the
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @author      Antoine Hedgecock <antoine@pmg.se>
 * @author      Jonas Eriksson <jonas@pmg.se>
 *
 * @copyright   2011-2013 Antoine Hedgecock
 * @license     http://www.opensource.org/licenses/bsd-license.php  BSD License
 */

namespace MCNLinkedIn\Options;

use Zend\Stdlib\AbstractOptions;
use Zend\Uri\Http;
use Zend\Validator\Uri;

/**
 * Class LinkedInOptions
 * @package MCNLinkedIn\Options
 */
class ApiServiceOptions extends AbstractOptions
{
    /**
     * Api secret
     *
     * @var string
     */
    protected $key;

    /**
     * Api key
     *
     * @var string
     */
    protected $secret;

    /**
     * @var string
     */
    protected $authenticationEndPoint;

    /**
     * @var array
     */
    protected $scope = array();

    /**
     * @var string
     */
    protected $accessToken;

    /**
     * @var array
     */
    protected $profileFields = array(
        'id', 'first-name', 'last-name',
        'headline', 'picture-url', 'email-address', 'primary-twitter-account', 'main-address'
    );

    /**
     * @param $profileFields
     */
    public function setProfileFields($profileFields)
    {
        $this->profileFields = $profileFields;
    }

    /**
     * @return array
     */
    public function getProfileFields()
    {
        return $this->profileFields;
    }

    /**
     * @param string $accessToken
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * @param array $scope
     */
    public function setScope(array $scope)
    {
        $this->scope = $scope;
    }

    /**
     * @return array
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @param string $authEndPoint
     *
     * @throws Exception\InvalidArgumentException
     */
    public function setAuthenticationEndPoint($authEndPoint)
    {
        $validator = new Uri(array('allowRelative' => false, 'uriHandler' => new Http));

        if (! $validator->isValid($authEndPoint)) {

            throw new Exception\InvalidArgumentException(
                sprintf('Invalid http uri authentication end point uri "%s".', $authEndPoint)
            );
        }

        $this->authenticationEndPoint = $authEndPoint;
    }

    /**
     * @return string
     */
    public function getAuthenticationEndPoint()
    {
        return $this->authenticationEndPoint;
    }

    /**
     * @param $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param $secret
     */
    public function setSecret($secret)
    {
        $this->secret = $secret;
    }

    /**
     * @return mixed
     */
    public function getSecret()
    {
        return $this->secret;
    }
}
