/*
YUI 3.10.3 (build 2fb5187)
Copyright 2013 Yahoo! Inc. All rights reserved.
Licensed under the BSD License.
http://yuilibrary.com/license/
*/

YUI.add("datatype-xml-format",function(e,t){var n=e.Lang;e.mix(e.namespace("XML"),{format:function(e){try{if(!n.isUndefined(e.getXml))return e.getXml();if(!n.isUndefined(XMLSerializer))return(new XMLSerializer).serializeToString(e)}catch(t){return e&&e.xml?e.xml:n.isValue(e)&&e.toString?e.toString():""}}}),e.namespace("DataType"),e.DataType.XML=e.XML},"true");