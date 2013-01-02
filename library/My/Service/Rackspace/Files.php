<?php
/**
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 */

/**
 * Extends Zend_Service_Rackspace_Files
 * - Fixed:
 *   storeObject    - allow to specify <b>Content-Type</b> 
 *   or specify multiple headers as an array
 *   storeObject    - allow to store empty objects
 *   getInfoAccount - get <b>all</b> account metadata information
 * - New methods:
 *   setAccountMetadataKey - allows to set metadata header X-Account-Meta-Temp-URL-Key
 *   createPseudoDirectory - creates nested pseudo-hierarchical directories
 *   setObjectHeaders      - allows to set multiple headers for existing object
 *
 * @category   My
 * @package    Service
 * @subpackage Rackspace
 * @author     Bernard Baltrusaitis <bernard@runawaylover.info>
 * @license    http://www.opensource.org/licenses/mit-license.html MIT License
 * @version    1.0
 */

class My_Service_Rackspace_Files extends Zend_Service_Rackspace_Files
{
    const HEADER_CONTENT_DISPOSITION = "Content-Disposition";
    const METADATA_ACCOUNT_HEADER    = "X-Account-";
    const ACCOUNT_TEMP_URL_KEY       = "X-Account-Meta-Temp-Url-Key";
    const HEADER_OBJECT_DELETE_AT    = "X-Delete-At";
    const HEADER_OBJECT_DELETE_AFTER = "X-Delete-After";
    
    /**
     * Constructor, provide your Rackspace authentication credentials,
     * or leave blank if use cached credentials
     * 
     * @param string|null $user
     * @param string|null $key
     * @param string|null $authUrl
     * @throws Zend_Service_Rackspace_Exception 
     */
    public function __construct($user = null, $key = null, $authUrl = null)
    {
        if (!is_null($user)) {
            $this->setUser($user);
        }
        
        if (!is_null($key)) {
            $this->setKey($key);
        }
        
        if (is_null($authUrl)) {
            $authUrl = self::US_AUTH_URL;
        }
        
        if (!in_array($authUrl, array(self::US_AUTH_URL, self::UK_AUTH_URL))) {
            throw new Zend_Service_Rackspace_Exception("The authentication URL should be valid");
        }
        
        $this->setAuthUrl($authUrl);
    }
    
    /**
     * Store a file in a container, if $headers is string
     * <b>'Content-Type'</b> is assumed for compability reasons
     * otherwise array of headers is expected or null
     * 
     * @param string $container container name
     * @param string $object object name
     * @param string $content object content
     * @param array $metadata object metadata
     * @param null|string|array $headers object headers
     * @return boolean
     * @throws Zend_Service_Rackspace_Exception 
     */
    public function storeObject($container, $object, $content = '', 
        $metadata = null, $headers = null)
    {
        if (is_string($headers)) {
            $headers = array(self::HEADER_CONTENT_TYPE => $headers);
        } elseif (is_null($headers)) {
            $headers = array();
        }
        
        if (empty($container)) {
            throw new Zend_Service_Rackspace_Exception(self::ERROR_PARAM_NO_NAME_CONTAINER);
        }
        
        if (empty($object)) {
            throw new Zend_Service_Rackspace_Exception(self::ERROR_PARAM_NO_NAME_OBJECT);
        }
        
        if (is_array($metadata)) {
            foreach ($metadata as $key => $value) {
                $headers[self::METADATA_OBJECT_HEADER . $key] = $value;
            }
        }
        
        $headers[self::HEADER_HASH]           = md5($content);
        $headers[self::HEADER_CONTENT_LENGTH] = strlen($content);
        
        $url = $this->getStorageUrl() . '/' 
            . rawurlencode($container) . '/' 
            . rawurlencode($object); 
        
        $result = $this->httpCall($url, 'PUT', $headers, null, $content);
        $status = $result->getStatus();
        switch ($status) {
            case '201': // break intentionally omitted
                return true;
            case '412':
                $this->errorMsg = self::ERROR_OBJECT_MISSING_PARAM;
                break;
            case '422':
                $this->errorMsg = self::ERROR_OBJECT_CHECKSUM;
                break;
            default:
                $this->errorMsg = $result->getBody();
                break;
        }
        
        $this->errorCode = $status;
        
        return false;
    }
    
    /**
     * Create nested pseudo-hierarchical directories specified in the $pathname
     * 
     * @param string $container container name
     * @param string $pathname pseudo-hierarchical directories to create
     * @return null
     * @throws Zend_Service_Rackspace_Exception 
     */
    public function createPseudoDirectory($container, $pathname)
    {
        if (empty($container)) {
            throw new Zend_Service_Rackspace_Exception(self::ERROR_PARAM_NO_NAME_CONTAINER);
        }
        
        if (empty($pathname)) {
            throw new Zend_Service_Rackspace_Exception(self::ERROR_PARAM_NO_NAME_OBJECT);
        }
        
        if (false === strpos($pathname, '/')) {
            $directories = $pathname;
        } else {
            $pathname = ltrim($pathname, '/');
            $directories = explode('/', $pathname);
        }
        
        $index = 0;
        $name  = '';
        foreach ($directories as $directory) {
            $name .= ($index ? '/' : '') . $directory;
            $this->storeObject($container, $name, '', array(), 'application/directory');
            $index++;
        }
    }
    
    /**
     * Set various headers for existing object
     * 
     * @param string $container container name
     * @param string $object object name
     * @param array $headers specify object headers
     * @return boolean true if action was successful
     */
    public function setObjectHeaders($container, $object, $headers = array())
    {
        $url = $this->getStorageUrl() . '/' 
            . rawurlencode($container) . '/' 
            . rawurlencode($object);
        
        $result = $this->httpCall($url, 'POST', $headers);
        
        if ($result->isSuccessful()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Set account metadata key
     * <b>Once the key is set, you should not change it</b>
     * 
     * @param string $key metadata key
     * @return boolean
     */
    public function setAccountMetadataKey($key)
    {
        $headers = array(
            self::AUTHTOKEN            => $this->getToken(),
            self::ACCOUNT_TEMP_URL_KEY => $key
        );
        
        $result = $this->httpCall($this->getStorageUrl(), 'POST', $headers);
        
        return $result->isSuccessful();
    }
    
    /**
     * Get account metadata information
     * 
     * @return array|false array of specified account metadata 
     */
    public function getInfoAccount()
    {
        $result = $this->httpCall($this->getStorageUrl(), 'HEAD');
        
        if ($result->isSuccessful()) {
            $headers    = $result->getHeaders();
            $metadata   = array();
            $headerName = ucwords(strtolower(self::METADATA_ACCOUNT_HEADER)); 

            foreach ($headers as $type => $value) {
                if (false !== strpos($type, $headerName)) {
                    $metadata[strtolower(substr($type, $count))] = $value;
                }
            }

            return $metadata;
        }
        
        return false;
    }
    
    /**
     * Set previously cached Cloud Files information
     * 
     * @param array $credentials
     * @return My_Service_Rackspace_Files 
     */
    public function setCredentials(array $credentials)
    {
        $whitelist = array('token', 'storageUrl', 'cdnUrl', 'managementUrl');
        
        foreach ($credentials as $name => $value) {
            if (in_array($name, $whitelist)) {
                $this->$name = $value;
            }
        }
        
        return $this;
    }    
    
    /**
     * Export Cloud Files information
     * 
     * @return array 
     */
    public function getCredentials()
    {
        return array(
            'token'         => $this->getToken(), 
            'storageUrl'    => $this->getStorageUrl(), 
            'cdnUrl'        => $this->getCdnUrl(), 
            'managementUrl' => $this->getManagementUrl()
        );
    }
}