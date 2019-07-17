#!/bin/bash

msg="make plugin success."
plugin_file="SimpleNemPay4.tar.gz"

cur_dir=`dirname "${0}"`

# tar.gz生成
COPYFILE_DISABLE=1 tar -zcvf ./$plugin_file SimpleNemPayEvent.php SimpleNemPayNav.php SimpleNemPayTwigBlock.php PluginManager.php composer.json Command Controller Entity Form Repository Resource Service

echo ""
echo "======================"
echo $msg
echo plugin file = $cur_dir/$plugin_file
echo "======================"
echo ""
