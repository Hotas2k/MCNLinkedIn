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

namespace MCNLinkedTest\Authentication\Adapter;

use DateTime;
use MCNLinkedIn\Authentication\Adapter\LinkedIn as Adapter;
use MCNLinkedIn\Authentication\Result;
use MCNLinkedIn\Options\Authentication\Adapter\LinkedIn as Options;
use MCNLinkedIn\Service\Exception\ApiException;
use Zend\Http\Request;

/**
 * Class LinkedInTest
 * @package MCNEmailTest\Authentication\Adapter
 */
class LinkedInTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Zend\Http\Request
     */
    protected $request;

    /**
     * @var \MCNLinkedIn\Options\Authentication\Adapter\LinkedIn
     */
    protected $options;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $service;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $userService;

    /**
     * @var \MCNLinkedIn\Authentication\Adapter\LinkedIn
     */
    protected $adapter;

    protected function setUp()
    {
        $this->options = new Options();
        $this->request = new Request();
        $this->service     = $this->getMock('MCNLinkedIn\Service\Api', array('requestAccessToken', 'getProfile', 'getOAuth2Uri'));
        $this->userService = $this->getMock('MCNStdlib\Interfaces\UserServiceInterface');

        $this->adapter = new Adapter($this->service);
        $this->adapter->setOptions($this->options);
    }

    public function testAuthenticate_ReturnHttpResponseIfNoCodeOrErrorSet()
    {
        $this->service
            ->expects($this->once())
            ->method('getOAuth2Uri')
            ->will($this->returnValue('uri'));

        $response = $this->adapter->authenticate($this->request, $this->userService);

        $this->assertInstanceOf('Zend\Http\Response', $response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('uri', $response->getHeaders()->get('Location')->getFieldValue());
    }

    public function testAuthenticate_ReturnProperResultErrorCodeOnError()
    {
        $this->request->getQuery()->set('error', 'access_denied');
        $this->request->getQuery()->set('error_description', 'description');

        $result = $this->adapter->authenticate($this->request, $this->userService);
        $this->assertInstanceOf('MCNLinkedIn\Authentication\Result', $result);
        $this->assertEquals(Result::FAILURE_ACCESS_DENIED, $result->getCode());
        $this->assertEquals('description', $result->getMessage());
        $this->assertNull($result->getIdentity());

        $this->request->getQuery()->set('error', 'other_error');

        $result = $this->adapter->authenticate($this->request, $this->userService);
        $this->assertInstanceOf('MCNLinkedIn\Authentication\Result', $result);
        $this->assertEquals(Result::FAILURE_INVALID_CONFIGURATION, $result->getCode());
        $this->assertEquals('description', $result->getMessage());
        $this->assertNull($result->getIdentity());
    }

    public function testAuthenticate_ReturnUserNotFound()
    {
        $this->request->getQuery()->set('code', 'hash');

        $token = new \stdClass();
        $token->access_token = 'token';
        $token->expires_in   = 3600;

        $profile = new \stdClass();
        $profile->id = '1';

        $this->service
            ->expects($this->once())
            ->method('requestAccessToken')
            ->with('hash')
            ->will($this->returnValue($token));

        $this->service
            ->expects($this->once())
            ->method('getProfile')
            ->will($this->returnValue($profile));

        $this->userService
            ->expects($this->once())
            ->method('getOneBy')
            ->with($this->options->getEntityIdProperty(), $profile->id)
            ->will($this->returnValue(null));

        $result = $this->adapter->authenticate($this->request, $this->userService);
        $this->assertInstanceOf('MCNLinkedIn\Authentication\Result', $result);
        $this->assertEquals(Result::FAILURE_IDENTITY_NOT_FOUND, $result->getCode());
        $this->assertEquals(Result::MSG_IDENTITY_NOT_FOUND, $result->getMessage());
        $this->assertNull($result->getIdentity());
    }

    public function testAuthenticate_UpdatesUserOnSuccess()
    {
        $this->request->getQuery()->set('code', 'hash');

        $token = new \stdClass();
        $token->access_token = 'token';
        $token->expires_in   = 3600;

        $profile = new \stdClass();
        $profile->id = '1';

        $user = $this->getMock('MCNLinkedInTest\TestAsset\AbstractApiConsumer');
        $user->expects($this->once())
            ->method('set' . ucfirst($this->options->getEntityTokenProperty()))
            ->with('token');

        $user->expects($this->once())
            ->method('set' . ucfirst($this->options->getEntityTokenExpiresAtProperty()))
            ->with($this->equalTo(DateTime::createFromFormat('U', time() + 3600)));

        $this->service
            ->expects($this->once())
            ->method('requestAccessToken')
            ->will($this->returnValue($token));

        $this->service
            ->expects($this->once())
            ->method('getProfile')
            ->will($this->returnValue($profile));

        $this->userService
            ->expects($this->once())
            ->method('getOneBy')
            ->with($this->options->getEntityIdProperty(), $profile->id)
            ->will($this->returnValue($user));

        $this->userService
            ->expects($this->once())
            ->method('save')
            ->with($user);

        $result = $this->adapter->authenticate($this->request, $this->userService);

        $this->assertInstanceOf('MCNLinkedIn\Authentication\Result', $result);
        $this->assertEquals(Result::SUCCESS, $result->getCode());
        $this->assertSame($user, $result->getIdentity());
    }

    public function testAuthenticate_ReturnsUncategorizedFailureOnApiError()
    {
        $this->request->getQuery()->set('code', 'hash');

        $this->service
            ->expects($this->once())
            ->method('requestAccessToken')
            ->will($this->throwException(new ApiException('message')));

        $result = $this->adapter->authenticate($this->request, $this->userService);

        $this->assertInstanceOf('MCNLinkedIn\Authentication\Result', $result);
        $this->assertEquals(Result::FAILURE_UNCATEGORIZED, $result->getCode());
        $this->assertNull($result->getIdentity());
        $this->assertEquals('message', $result->getMessage());
    }
}
