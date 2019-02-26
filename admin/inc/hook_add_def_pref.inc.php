<?php
  use Expresso\Core\GlobalService;

  GlobalService::get('pref')->change('common','maxmatchs','15');
  GlobalService::get('pref')->change('common','theme','default');
  GlobalService::get('pref')->change('common','tz_offset',0);
  GlobalService::get('pref')->change('common','dateformat','m/d/Y');
  GlobalService::get('pref')->change('common','timeformat',12);
  GlobalService::get('pref')->change('common','lang','en');
?>
