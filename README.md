# SimpleNemPay for EC-CUBE4

## Overview
EC-CUBE 4 series Nem (Xem) payment plug-in.
By installing this plug-in you can settle in Xem.

## Install
1. Create plug-in file
```bash
$ git clone git@github.com:yusukeito58/nempay-eccube4.git
$ cd nempay-eccube4
$ sh make_plugin.sh
```

2. Install on EC-CUBE
Install the created plug-in(SimpleNemPay.tar.gz) from "owner's store > plugin > plugin list"

Next, install library.
```
$ bin/console eccube:composer:require endroid/qr-code
```

3. Plug-in setting
Register an auctioneer account (deposit destination)
**※ When testing, switch "Environment switch" to test environment(testnet)**

4. Payment confirmation setting
Set up confirmation program to activate payment every fixed time

```bash
# set crontab
$ crontab -e
*/5 * * * * cd /var/www/html/; bin/console simple_nem_pay:remittance_confirm 
```
  
## Licence

[GNU](https://github.com/yusukeito58/nempay-eccube4/blob/master/LICENSE)

## Author

[yusukeito58](https://github.com/yusukeito58)
