<?php

/*
 *
 * --- This library is not official. If you need more information about what you can do with the API,
 * --- always refer to the documentation at http://docs.cyberimpactapiv4.apiary.io/ for more information
 *
 */

namespace Cyberimpact;

use Exception;

class ApiClientV4
{
    const API_URL = 'https://apiv4.cyberimpact.com/';

    // Authentication methods
    const METHOD_BASIC = 'basic';
    const METHOD_JWT = 'jwt';

    // Response types
    const RETURN_AS_JSON_STRING = 1;
    const RETURN_AS_JSON_OBJECT = 2;
    const RETURN_AS_ASSOC_ARRAY = 3;

    // Count types
    const COUNT_MEMBERS = 1;
    const COUNT_UNSUBSCRIBED = 2;
    const COUNT_BOUNCED = 3;
    const COUNT_MEMBERS_GROUPS = 4;
    const COUNT_GROUPS = 5;
    const COUNT_GROUP_MEMBERS = 6;
    const COUNT_SENT_MAILINGS = 7;
    const COUNT_SCHEDULED_MAILINGS = 8;
    const COUNT_TEMPLATES = 9;

    private $user;
    private $password;
    private $method;
    private $responseType = self::RETURN_AS_JSON_STRING;

    /**
     * @param string $method Authentication method (self::METHOD_BASIC or self::METHOD_JWT)
     * @param string $user Username or Token
     * @param string $password Password for Basic Auth
     * @throws Exception if $method is not basic or jwt
     */
    public function __construct($method = '', $user = '', $password = '')
    {
        if (!empty($method)) {
            $this->setMethod($method);
        }

        if (!empty($user)) {
            $this->setUser($user);
        }

        if (!empty($password)) {
            $this->setPassword($password);
        }
    }

    public function setMethod($method)
    {
        if (in_array($method, array(
            self::METHOD_BASIC,
            self::METHOD_JWT
        ))) {
            $this->method = $method;
        } else {
            throw new Exception('Invalid authentication method. Make sure to use a valid constant as parameter.');
        }
    }

    public function setUser($user)
    {
        $this->user = $user;
    }

    public function setToken($token)
    {
        $this->setUser($token);
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function setResponseType($type)
    {

        if (in_array($type, array(
            self::RETURN_AS_JSON_STRING,
            self::RETURN_AS_JSON_OBJECT,
            self::RETURN_AS_ASSOC_ARRAY
        ))) {
            $this->responseType = $type;
        } else {
            throw new Exception('Invalid response type. Make sure to use a valid constant as parameter.');
        }

    }


    /**
     * @param string $request_type Type of the request (post, put, delete, patch, get)
     * @param string $request URL requested
     * @param array $params List of parameters to be added to Query String or Post Values
     * @throws Exception if $request_type is not valid or supported
     * @returns string JSON object containing the result of the request
     */
    private function send($request_type, $request, array $params = array())
    {

        $queryString = '';

        $ch = curl_init();
        $this->setLoginMethod($ch);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        // TODO Remove once dev is done
        curl_setopt($ch, CURLOPT_VERBOSE, true);

        // TODO add method to add certificate
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        switch ($request_type) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if (!empty($params)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
                }
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                if (!empty($params)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
                }
                break;
            case 'PATCH':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                if (!empty($params)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
                }
                break;
            case 'GET':
                if (!empty($params)) {
                    $queryString = '?' . http_build_query($params);
                }
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            default:
                throw new Exception('Invalid request type');
                break;
        }

        // Building the complete request
        curl_setopt($ch, CURLOPT_URL, self::API_URL . $request . $queryString);

        $response = curl_exec($ch);
        curl_close($ch);

        if ($this->responseType == self::RETURN_AS_JSON_STRING) {
            return $response;
        } else {
            if ($this->responseType == self::RETURN_AS_JSON_OBJECT) {
                return json_decode($response);
            } else {
                if ($this->responseType == self::RETURN_AS_ASSOC_ARRAY) {
                    return json_decode($response, true);
                } else {
                    throw new Exception('Invalid response type.');
                }
            }
        }

    }

    private function setLoginMethod($ch)
    {
        if ($this->method == self::METHOD_BASIC) {
            return curl_setopt($ch, CURLOPT_USERPWD, $this->user . ":" . $this->password);
        } elseif ($this->method == self::METHOD_JWT) {
            return curl_setopt($ch, CURLOPT_HTTPHEADER,
                array("Content-Type: application/json", "Authorization: Bearer " . $this->user));
        } else {
            throw new Exception('No authentication method was set.');
        }
    }

    public function getTotalCount($countConst)
    {

        $tmpResponseType = $this->responseType;
        $this->setResponseType(self::RETURN_AS_ASSOC_ARRAY);

        if ($countConst == self::COUNT_MEMBERS) {
            $totalCount = $this->getMembers(1, 1);
        } elseif ($countConst == self::COUNT_UNSUBSCRIBED) {
            $totalCount = $this->getUnsubscribedMembers(1, 1);
        } elseif ($countConst == self::COUNT_BOUNCED) {
            $totalCount = $this->getBouncedMembers(1, 1);
        } elseif ($countConst == self::COUNT_MEMBERS_GROUPS) {
            $totalCount = $this->getMemberGroups(1, 1);
        } elseif ($countConst == self::COUNT_GROUPS) {
            $totalCount = $this->getGroups(1, 1);
        } elseif ($countConst == self::COUNT_GROUP_MEMBERS) {
            $totalCount = $this->getGroupMembers(1, 1);
        } elseif ($countConst == self::COUNT_SENT_MAILINGS) {
            $totalCount = $this->getSentMailings(1, 1);
        } elseif ($countConst == self::COUNT_SCHEDULED_MAILINGS) {
            $totalCount = $this->getScheduledMailings(1, 1);
        } elseif ($countConst == self::COUNT_TEMPLATES) {
            $totalCount = $this->getTemplates(1, 1);
        } else {
            throw new Exception('Invalid request. Make sure to use a valid constant as parameter.');
        }

        $this->setResponseType($tmpResponseType);

        if ($totalCount) {
            $returnCount = $totalCount['totalCount'];
            return $returnCount;
        } else {
            throw new Exception('Counting error');
        }

    }

    public function getMember($key)
    {

        return $this->send('GET', 'members/' . $key);

    }

    public function getMembers($page = 1, $limit = 100, $sort = 'email_asc')
    {

        $params = array(
            'page' => $page,
            'limit' => $limit,
            'sort' => $sort
        );

        return $this->send('GET', 'members', $params);

    }

    public function getMemberGroups($key, $page = 1, $limit = 100, $sort = 'title_asc')
    {

        $params = array(
            'page' => $page,
            'limit' => $limit,
            'sort' => $sort
        );

        return $this->send('GET', 'members/' . $key . '/groups', $params);

    }

    public function editMember($key, array $params)
    {

        return $this->send('PATCH', 'members/' . $key, $params);

    }


    // TODO fields validation
    public function replaceMember($key, array $params)
    {

        return $this->send('PUT', 'members/' . $key, $params);

    }

    public function replaceMemberGroups($key, array $groups)
    {

        $params = array(
            'groups' => implode(',', $groups)
        );

        return $this->send('PUT', 'members/' . $key . '/groups', $params);

    }

    public function deleteMember($key)
    {

        return $this->send('DELETE', 'members/' . $key);

    }

    public function unsubscribeMember($key)
    {

        return $this->send('POST', 'members/unsubscribed/' . $key);

    }

    public function addOptin(array $params)
    {

        return $this->send('POST', 'members/optins', $params);

    }

    public function addMember(array $params)
    {

        return $this->send('POST', 'members', $params);

    }

    public function addMemberToGroups($key, array $groups)
    {

        $params = array(
            'groups' => implode(',', $groups)
        );

        return $this->send('POST', 'members/' . $key . '/groups', $params);

    }

    public function getUnsubscribedMember($email)
    {

        return $this->send('GET', 'members/unsubscribed/' . $email);

    }

    public function getUnsubscribedMembers($page = 1, $limit = 100, $sort = 'email_asc')
    {

        $params = array(
            'page' => $page,
            'limit' => $limit,
            'sort' => $sort
        );

        return $this->send('GET', 'members/unsubscribed', $params);

    }

    public function getBouncedMembers($page = 1, $limit = 100, $sort = 'email_asc')
    {

        $params = array(
            'page' => $page,
            'limit' => $limit,
            'sort' => $sort
        );

        return $this->send('GET', 'members/bounced', $params);

    }

    public function getGroup($id)
    {

        return $this->send('GET', 'groups/' . $id);

    }

    public function getGroups($page = 1, $limit = 100, $sort = 'title_asc')
    {

        $params = array(
            'page' => $page,
            'limit' => $limit,
            'sort' => $sort
        );

        return $this->send('GET', 'groups', $params);

    }

    public function getGroupMembers($id, $page = 1, $limit = 100, $sort = 'title_asc')
    {

        $params = array(
            'page' => $page,
            'limit' => $limit,
            'sort' => $sort
        );

        return $this->send('GET', 'groups/' . $id . '/members', $params);

    }

    public function addGroup(array $params)
    {

        return $this->send('POST', 'groups', $params);

    }

    public function editGroup($id, array $params)
    {

        return $this->send('PATCH', 'groups/' . $id, $params);

    }

    // TODO parameters validation
    public function replaceGroup($id, array $params)
    {

        return $this->send('PUT', 'groups/' . $id, $params);

    }

    public function deleteGroup($id)
    {

        return $this->send('DELETE', 'groups/' . $id);

    }

    public function getSentMailings($page = 1, $limit = 100, $sort = 'date_asc')
    {

        $params = array(
            'page' => $page,
            'limit' => $limit,
            'sort' => $sort
        );

        return $this->send('GET', 'mailings/sent', $params);

    }

    public function getScheduledMailings($page = 1, $limit = 100, $sort = 'date_scheduled_asc')
    {

        $params = array(
            'page' => $page,
            'limit' => $limit,
            'sort' => $sort
        );

        return $this->send('GET', 'mailings/scheduled', $params);

    }

    public function getMailing($id)
    {

        return $this->send('GET', 'mailings/' . $id);

    }

    public function deleteMailing($id)
    {

        return $this->send('DELETE', 'mailings/' . $id);

    }

    public function addMailing(array $params)
    {

        return $this->send('POST', 'mailings', $params);

    }

    public function getTemplates($page = 1, $limit = 100, $sort = 'template_asc')
    {

        $params = array(
            'page' => $page,
            'limit' => $limit,
            'sort' => $sort
        );

        return $this->send('GET', 'templates', $params);

    }

    public function getTemplate($id)
    {

        return $this->send('GET', 'templates/' . $id);

    }

    public function addTemplate(array $params)
    {

        return $this->send('POST', 'templates', $params);

    }

    // TODO parameters validation
    public function replaceTemplate($id, array $params)
    {

        return $this->send('PUT', 'templates/' . $id, $params);

    }

    public function deleteTemplate($id)
    {

        return $this->send('DELETE', 'templates/' . $id);

    }

    public function getBatch($id)
    {

        return $this->send('GET', 'batches/' . $id);

    }

    public function addBatch()
    {

    }

    private function validateParameters($sentParameters, $requiredParameters)
    {

        $missingParameters = array();

        foreach ($sentParameters as $key => $value) {
            if (in_array($key, $requiredParameters) && empty($value)) {
                $missingParameters[] = $key;
            }
        }

        if (empty($missingParameters)) {
            return true;
        } else {
            throw new Exception('Some required parameters are missing (' . implode(', ', $missingParameters) . ').');
        }

    }

}
