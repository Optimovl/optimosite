<?php

//new umiEventListener("systemModifyElement", "content", "typograf_processElement");
//new umiEventListener("systemCreateElement", "content", "typograf_processElement");
new umiEventListener("systemCreateObject", "content", "typograf_processObject");
new umiEventListener("systemModifyObject", "content", "typograf_processObject");
//new umiEventListener("systemModifyPropertyValue", "content", "typograf_processProperty");