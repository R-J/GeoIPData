GeoIPData
=========

GeoIPData is an addon for Vanilla that fetches freely available GeoIP information from www.maxmind.com. Information is stored in the database sothat questioning GeoIP data could be made with simple sqls.   
By the time of creation, GeoLiteCity is around 25MB, unzipped into two files they consume 88MB.   
   
Downloading, unzipping, and uploading into the database takes quite long so that timeouts may happen. In order to avoid that, settings are split into several steps.
