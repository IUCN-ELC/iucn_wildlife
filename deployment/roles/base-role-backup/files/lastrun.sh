#!/bin/bash
# *********************************************************************
#  This file is a simplistic way to prevent same day runs of the backup
# script. 
#   *DO NOT* edit the numbers below unless you know what you're doing
# *********************************************************************
#
# Latest backup was performed on 2017-03-15_19-23-16
#
# Hint: date --date='2017-01-01 16:58:59' +%s or date --date='yesterday' +%s
# or use bc and the fact that 23h = 82800s
LASTRUN=1
