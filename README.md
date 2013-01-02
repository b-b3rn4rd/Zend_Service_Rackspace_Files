Zend_Service_Rackspace_Files
============================

Extended version of Zend_Service_Rackspace_Files with fixes and additional functionality

<h2>Fixes</h2>
<ul>
<li><b>__construct</b>    - allow to bypass params, (useful if Rackspace credentials are cached)</li>
<li><b>storeObject</b>    - allow to specify <b>Content-Type</b> or specify multiple headers as an array</li>
<li><b>storeObject</b>    - allow to store empty objects</li>
<li><b>getInfoAccount</b> - get <b>all</b> account metadata information</li>
</ul>

<h2>New methods</h2>
<ul>
  <li><b>setAccountMetadataKey</b> - allows to set metadata header X-Account-Meta-Temp-URL-Key</li>
  <li><b>createPseudoDirectory</b> - creates nested pseudo-hierarchical directories</li>
  <li><b>setObjectHeaders</b>      - set multiple headers for existing object</li>
  <li><b>getCredentials</b>        - allow to export credentials for future caching</li>
  <li><b>setCredentials</b>        - set previously cached Rackspace information, to avoid multiple authentication</li>
</ul>


