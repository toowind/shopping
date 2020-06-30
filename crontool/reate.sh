#! /bin/sh
PRO_NAME=Order/run
CMD=/usr/local/php/bin/php
DIR=/web/api-shop/shop-app-back
SHE_NAME="cron.php Order/run"
#用ps获取$PRO_NAME进程数量
  NUM=`ps aux | grep ${PRO_NAME} | grep -v grep |wc -l`
  echo $NUM
#少于1，重启进程
  if [ "${NUM}" -lt "1" ];then
    cd $DIR
    $CMD $SHE_NAME &
#大于1，杀掉所有进程，重启
  elif [ "${NUM}" -gt "1" ];then
    ps -ef | grep ${PRO_NAME} | grep -v "grep"  | cut -c 9-15 |xargs kill -9
    cd $DIR
    $CMD $SHE_NAME &
  fi
#kill僵尸进程
  NUM_STAT=`ps aux | grep ${PRO_NAME} | grep T | grep -v grep | wc -l`
  if [ "${NUM_STAT}" -gt "0" ];then
    ps -ef | grep ${PRO_NAME} | grep -v "grep"  | cut -c 9-15 |xargs kill -9
    cd $DIR
    $CMD $SHE_NAME &
  fi
exit 0
