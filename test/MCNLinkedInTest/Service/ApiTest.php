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

namespace MCNLinkedInTest\Service;

use MCNLinkedIn\Options\ApiServiceOptions;
use MCNLinkedIn\Service\Api;
use Zend\Http\Response;

/**
 * Class ApiTest
 * @package MCNLinkedInTest\Service
 */
class ApiTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->client  = $this->getMock('Zend\Http\Client');
        $this->options = new ApiServiceOptions();

        $this->service = new Api($this->options);
        $this->service->setHttpClient($this->client);
    }

    public function testGetHttpClient_CanLazyLoadClient()
    {
        $service = new Api();
        $client = $service->getHttpClient();
        $this->assertInstanceOf('Zend\Http\Client', $client);
    }

    public function testGetOAuth2Uri()
    {
        $csrf = 'digest';

        $this->options->setScope(array('field', 'bar', 'bar'));
        $this->options->setKey('awesome');
        $this->options->setAuthenticationEndPoint('http://domain.tld/');

        $result = $this->service->getOAuth2Uri($csrf);

        $uri = 'https://www.linkedin.com/uas/oauth2/authorization?response_type=code&' .
               'client_id=' . $this->options->getKey() . '&' .
               'scope=field+bar&' .
               'state=' . $csrf . '&' .
               'redirect_uri=http%3A%2F%2Fdomain.tld%2F';

        $this->assertEquals($uri, $result);
    }

    public function testRequestAccessToken_Success()
    {
        $data = new \stdClass();
        $data->access_token = 'token';
        $data->expires_in = 3600;

        $response = new Response();
        $response->setStatusCode(200);
        $response->setContent(json_encode($data));

        $this->client
            ->expects($this->once())
            ->method('setUri')
            ->with(Api::URI_OAUTH2_TOKEN);

        $this->client
            ->expects($this->once())
            ->method('setParameterGet')
            ->with(
                array(
                    'code'          => 'code',
                    'grant_type'    => 'authorization_code',
                    'client_id'     => $this->options->getKey(),
                    'client_secret' => $this->options->getSecret(),
                    'redirect_uri'  => $this->options->getAuthenticationEndPoint()
                )
            );

        $this->client
            ->expects($this->once())
            ->method('send')
            ->will($this->returnValue($response));

        $result = $this->service->requestAccessToken('code');
        $this->assertEquals($data, $result);
    }
}
