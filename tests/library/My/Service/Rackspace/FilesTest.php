<?php
class My_Service_Rackspace_FilesTest extends PHPUnit_Framework_TestCase
{
    /**
     * Reference to object
     * 
     * @var My_Service_Rackspace_Files 
     */
    protected $files = null;
    
    /**
     * Rackspace user
     * 
     * @var string 
     */
    protected $user = null;
    
    /**
     * Rackspace key
     * 
     * @var string 
     */
    protected $key  = null;
    
    /**
     * Container name
     * 
     * @var string 
     */
    protected $container = 'test';
    
    /**
     * Object name
     * 
     * @var string
     */
    protected $filename = 'hello.txt';
    
    public function setUp()
    {
        if (!defined('TEST_RACKSPACE_USER') || !defined('TEST_RACKSPACE_KEY')) {
            $this->markTestSkipped('Rackspace credentials are not specified');
        }
        
        $this->user = TEST_RACKSPACE_USER;
        $this->key  = TEST_RACKSPACE_KEY;
        
        $this->files = new My_Service_Rackspace_Files($this->user, $this->key);
    }
    
    public function testConstructorAllowNoAttributes()
    {
        $rackspace = new My_Service_Rackspace_Files();
        $this->assertInstanceOf('My_Service_Rackspace_Files', $rackspace);
        $this->assertNull($rackspace->getUser());
        $this->assertNull($rackspace->getKey());
    }
    
    /**
     * @expectedException Zend_Service_Rackspace_Exception 
     */
    public function testConstuctorExpectValidAuthUrl()
    {
        new My_Service_Rackspace_Files(null, null, 'invalidUrl');
    }
    
    public function testConstructorArgsSetValues()
    {
        $this->assertEquals($this->user, $this->files->getUser());
        $this->assertEquals($this->key, $this->files->getKey());
    }
    
    public function testDefaultAuthUrlIsUS()
    {
        $this->assertEquals(My_Service_Rackspace_Files::US_AUTH_URL, 
            $this->files->getAuthUrl());
    }
    
    /**
     * @expectedException Zend_Service_Rackspace_Exception 
     */
    public function testStoreObjectMethodRequiresContainerAndObjectArgs()
    {
        $this->files->storeObject('', '');
    }
    
    public function testStoreObjectAllowEmptyContent()
    {
        $success = $this->files->storeObject($this->container, $this->filename, '');
        $this->assertTrue($success);
    }
    
    public function testStoreObjectAllowToSpecifyContetTypeAsString()
    {
        $this->files->storeObject($this->container, $this->filename, '', null, 'text/plain');
        $object = $this->files->getObject($this->container, $this->filename);
        $this->assertEquals('text/plain', $object->getContentType());
    }
    
    public function testStoreObjectAllowToSpecifyHeaderAsArray()
    {
        $headers = array(
            My_Service_Rackspace_Files::HEADER_CONTENT_TYPE        => 'text/plain',
            My_Service_Rackspace_Files::HEADER_CONTENT_DISPOSITION => 'attachment; filename="new.txt"',
        );
        $this->files->storeObject($this->container, $this->filename, '', null, $headers);
        $object = $this->files->getObject($this->container, $this->filename);
        $this->assertEquals('text/plain', $object->getContentType());
    }
    
    /**
     * @expectedException Zend_Service_Rackspace_Exception  
     */
    public function testCreatePseudoDirectoryRequiresContainerAndPath()
    {
        $this->files->createPseudoDirectory('', '');
    }    
    
    public function testCreatePseudoDirectoryCreatesDirs()
    {
        $this->files->createPseudoDirectory($this->container, 'home/user1');
        $homeFolder = $this->files->getObject($this->container, 'home');
        $this->assertEquals('application/directory', $homeFolder->getContentType());
        $userFolder = $this->files->getObject($this->container, 'home/user1');
        $this->assertEquals('application/directory', $userFolder->getContentType());
        $this->files->storeObject($this->container, 'home/user1/one.txt');
        $this->files->storeObject($this->container, 'home/user1/two.txt');
        
        $response = $this->files->getObjects($this->container, array(
            'path' => 'home/user1/'
        ));
        
        $this->assertEquals(2, count($response));
    }
    
    public function testsetObjectHeadersIsAbleToSetHeaders()
    {
        $response = $this->files->setObjectHeaders($this->container, $this->filename, array(
            My_Service_Rackspace_Files::HEADER_CONTENT_DISPOSITION => 'attachment; filename="new.txt"',
        ));
        
        $this->assertTrue($response);
    }    
    
    public function testgetInfoAccountReturnAllParams()
    {
        $response = $this->files->getInfoAccount();
        $this->assertArrayHasKey('x-account-meta-temp-url-key', $response);
    }
    
    public function testGetCredentialsReturnArrayOfCredentials()
    {
        $credentials = $this->files->getCredentials();
        
        $this->assertArrayHasKey('token', $credentials);
        $this->assertArrayHasKey('storageUrl', $credentials);
        $this->assertArrayHasKey('cdnUrl', $credentials);
        $this->assertArrayHasKey('managementUrl', $credentials);
        
        $this->assertEquals($this->files->getToken(), $credentials['token']);
        $this->assertEquals($this->files->getStorageUrl(), $credentials['storageUrl']);
        $this->assertEquals($this->files->getCdnUrl(), $credentials['cdnUrl']);
        $this->assertEquals($this->files->getManagementUrl(), $credentials['managementUrl']);
    }
    
    /**
     * @expectedException Exception 
     */
    public function testSetCredentialsExpectArray()
    {
        $this->files->setCredentials(false);
    }
    
    public function testSetCredentialsSetRackspaceInformation()
    {
        $rackspace = new My_Service_Rackspace_Files();
        $rackspace->setCredentials($this->files->getCredentials());
        $this->assertEquals($this->files->getToken(), $rackspace->getToken());
        $this->assertEquals($this->files->getStorageUrl(), $rackspace->getStorageUrl());
        $this->assertEquals($this->files->getCdnUrl(), $rackspace->getCdnUrl());
        $this->assertEquals($this->files->getManagementUrl(), $rackspace->getManagementUrl());
    }
    
    public function testSetCredentialsAvoidAuthentication()
    {
        $stub = $this->getMock('My_Service_Rackspace_Files', array('authenticate'));
        $stub->expects($this->never())
             ->method('authenticate');
        
        $stub->setCredentials($this->files->getCredentials());
        $this->assertInternalType('array', $stub->getInfoAccount());
    }    
}