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

namespace MCNLinkedIn\Authentication\Adapter;

use DateTime;
use MCNLinkedIn\Authentication\Result;
use MCNStdlib\Interfaces\UserServiceInterface;
use MCNUser\Authentication\Adapter\AbstractAdapter;
use Zend\Http\Request as HttpRequest;
use MCNLinkedIn\Service\Api as ApiService;
use Zend\Http\PhpEnvironment\Response as HttpResponse;

/**
 * Class LinkedIn
 * @package MCNLinkedIn\Authentication\Plugin
 */
class LinkedIn extends AbstractAdapter
{
    /**
     * @var \MCNLinkedIn\Service\Api
     */
    protected $apiService;

    /**
     * @param ApiService $api
     */
    public function __construct(ApiService $api)
    {
        $this->apiService = $api;
    }

    /**
     * @inheritdoc
     */
    public function authenticate(HttpRequest $request, UserServiceInterface $service)
    {
        $code  = $request->getQuery('code');
        $error = $request->getQuery('error');

        if (! $code && ! $error) {

            $response = new HttpResponse();
            $response->getHeaders()->addHeaderLine('Location', $this->apiService->getOAuth2Uri());
            $response->setStatusCode(302);
            return $response;

        } else {

            if ($error) {

                $code = $error == 'access_denied' ? Result::FAILURE_ACCESS_DENIED
                                                  : Result::FAILURE_INVALID_CONFIGURATION;

                return Result::create($code, null, $request->getQuery('error_description'));
            }

            $result = $this->apiService->requestAccessToken($code);

            $this->apiService->setAccessToken($result['access_token']);
            $profile = $this->apiService->getProfile();

            $user = $service->getOneBy('linkedin_id', $profile['id']);

            if (! $user) {

                return Result::create(Result::FAILURE_IDENTITY_NOT_FOUND, null, Result::MSG_IDENTITY_NOT_FOUND);
            }

            return Result::create(Result::SUCCESS, $user);
        }
    }
}
