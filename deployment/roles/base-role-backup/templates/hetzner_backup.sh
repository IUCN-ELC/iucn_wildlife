#!/bin/bash

CR=$'\n'
TABS=$'\t\t'
RSYNC='/usr/bin/rsync'
MOUNTCIFS='/usr/sbin/mount.cifs'
MAIL='/usr/bin/nail'
HOSTN="{{server_hostname}}"
IP="{{server_ip}}"
TODAY=$( date +%Y-%m-%d_%H-%M-%S )
TODAYSEC=$( date +%s )
WARNINTV=82800
EMAILREC="{{backup_emailrec}}"


HCUSTOMER="{{backup_hzcust}}"
HPROJECT="{{backup_hzproject}}"
HSITE="{{backup_hzsite}}"
HBCKHIST="{{backup_hzhistlen}}"
HBCKUSR="{{backup_hzuser}}"
HBCKPWD="{{backup_hzpwd}}"
HBCKDEV="//${HBCKUSR}.your-backup.de/backup"
HBCKDEVDIR="{{backup_hzbckdevdir}}"
TARGETDIR="{{backup_hztargetdir}}"
TEMPLOCAL="{{backup_hztemp}}"
HBCK_PARTIAL=0
HBCK_STATUS=0
declare -a HBCK_MSGS

. /etc/cron.edw/lastrun.sh

preparations()  {

	if ! PREP=$( rm -f ${TEMPLOCAL}/* 2>&1 1>/dev/null ) ; then
		HBCK_PARTIAL=1
		HBCK_MSGS+="$CR[ERROR] in prep:  rm -f ${TEMPLOCAL}/* 2>&1 1>/dev/null $CR $PREP $CR"
	fi

## Ansible loop: for command in h_backup_prep
{% for command in backup_hzprep %}
	if ! PREP=$( {{command}} ) ; then
		RBCK_PARTIAL=1
		RBCK_MSGS+="$CR[ERROR] in prep: {{command}} $CR $PREP $CR"
	fi
{% endfor %}
## End Ansible loop
	return $HBCK_PARTIAL

}


hbackup() {
	if ! MKLOG=$( touch ${TEMPLOCAL}/eholcim_bck_on_hetzner ) ; then
		HBCK_PARTIAL=1
		RBCK_MSGS+="$CR[ERROR]  Creating log file: touch ${TEMPLOCAL}/eholcim_bck_on_hetzner $CR $MKLOG $CR "
	fi

	if [ ! -d "${TARGETDIR}" ] ; then
		HBCK_STATUS=1
		HBCK_MSGS+="$CR[ERROR]  Checking ${TARGETDIR} $CR ${TARGETDIR} mountpoint does not exist $CR"
	fi

	if [ ! -x $RSYNC ] ; then
		HBCK_STATUS=1
		HBCK_MSGS+="$CR[ERROR]  Checking for rsync command $CR $RSYNC   not found $CR"
	fi

	if [ ! -x $MOUNTCIFS ] ; then
		HBCK_STATUS=1
		HBCK_MSGS+="$CR[ERROR]  Checking for mount.cifs command $CR $MOUNTCIFS   not found $CR"
	fi

	if [ ! -x $MAIL ] ; then
		HBCK_STATUS=1
		HBCK_MSGS+="$CR[ERROR]  Checking for required email command $CR $MAIL   not found $CR"
	fi

	DEVCHK=$( mount | grep "$HBCKDEVDIR" )
	DEVCHKSTATUS=$?

	TARGETCHK=$( mount | grep "$TARGETDIR" )
	TARGETCHKSTATUS=$?

	if [ $DEVCHKSTATUS -eq 0 ] || [ $TARGETCHKSTATUS -eq 0 ] ; then
		HBCK_STATUS=1
		if [ ! -z "${DEVCHK}" ] ; then
			HBCK_MSGS+="$CR[ERROR]  Diskimage dir $HBCKDEVDIR is busy $CR $DEVCHK $CR"
		fi
		if [ ! -z "${TARGETCHK}" ] ; then
			HBCK_MSGS+="$CR[ERROR]  Destination dir $TARGETDIR is busy $CR $TARGETCHK $CR"
		fi
	fi
	if [ $HBCK_STATUS -eq 0 ] ; then
		# Will you promise you'll_never_use_blanks_in_folder_names ? :-(
		HSTATUS=$( /usr/sbin/mount.cifs -o user=${HBCKUSR},pass=${HBCKPWD} ${HBCKDEV} "${HBCKDEVDIR}" 2>&1 1>/dev/null )
		if [ ! $? -eq 0 ] ; then
			HBCK_STATUS=1
			HBCK_MSGS+="$CR[ERROR]  running: /usr/sbin/mount.cifs -o user=${HBCKUSR},pass=,,, ${HBCKDEV} "${HBCKDEVDIR}" 2>&1 1>/dev/null $CR $HSTATUS $CR"
		else
			HBCK_MSGS+="$CR[OK] /usr/sbin/mount.cifs -o user=${HBCKUSR},pass=... ${HBCKDEV} "${HBCKDEVDIR}" 2>&1 1>/dev/null"
		fi

		if [ ! -f "${HBCKDEVDIR}/${HOSTN}/daily/BackupDataFs.lock" ] ; then
			if MKLOCK=$( touch "${HBCKDEVDIR}/${HOSTN}/daily/BackupDataFs.lock" ) ; then
				HBCK_MSGS+="$CR[OK] created ${HBCKDEVDIR}/${HOSTN}/daily/BackupDataFs.lock"
				TGTSTATUS=$( mount ${HBCKDEVDIR}/${HOSTN}/daily/backup_data_fs.img $TARGETDIR -o loop )

				if [ ! $? -eq 0 ] ; then
					HBCK_STATUS=1
					HBCK_MSGS+="$CR[ERROR] mount ${HBCKDEVDIR}/${HOSTN}/daily/backup_data_fs.img $TARGETDIR -o loop $CR $TGTSTATUS $CR "
				else
					HBCK_MSGS+="$CR[OK] mount ${HBCKDEVDIR}/${HOSTN}/daily/backup_data_fs.img $TARGETDIR -o loop "

					# "latest" meaning 1_day_ago
					# latest -> temp
					# ${HBCKHIST}_days_ago -> latest
					# n=${HBCKHIST} ; n>2 ; n--
					#	n_days_ago-1 -> n_days_ago
					# temp -> 2_days_ago
					mv ${TARGETDIR}/${HOSTN}/daily/latest ${TARGETDIR}/${HOSTN}/daily/temp
					mv ${TARGETDIR}/${HOSTN}/daily/${HBCKHIST}_days_ago ${TARGETDIR}/${HOSTN}/daily/latest
					for ((i=$HBCKHIST;i>2;i--)) ; do
						# echo mv ${TARGETDIR}/${HOSTN}/daily/$((${i}-1))_days_ago ${TARGETDIR}/${HOSTN}/daily/${i}_days_ago
						mv ${TARGETDIR}/${HOSTN}/daily/$((${i}-1))_days_ago ${TARGETDIR}/${HOSTN}/daily/${i}_days_ago
					done
					mv ${TARGETDIR}/${HOSTN}/daily/temp ${TARGETDIR}/${HOSTN}/daily/2_days_ago
				fi
			else
				HBCK_STATUS=1
				HBCK_MSGS+="$CR[ERROR] creating ${HBCKDEVDIR}/${HOSTN}/daily/BackupDataFs.lock file $CR $MKLOCKi $CR"
			fi
		else
			HBCK_STATUS=1
			HBCK_MSGS+="$CR[ERROR] lockfile ${HBCKDEVDIR}/${HOSTN}/daily/BackupDataFs.lock already exists! $CR mount of ${HBCKDEVDIR}/${HOSTN}/daily/backup_data_fs.img aborted $CR "
		fi
	fi

	if [ $HBCK_STATUS -eq 0 ] ; then
		rm -f "${TARGETDIR}/${HOSTN}/daily/latest/logs/eholcim_bck_on_hetzner"

## Ansible loop: "for path in back_up_paths"
{% for path in backup_paths %}
		RSYNC=$( rsync --delete --log-file="${TEMPLOCAL}/eholcim_bck_on_hetzner" --log-file-format='%o: %B %f %L %l %b' -avAX{% if path.exclude %}{% for xcl in path.exclude %} --exclude="{{xcl}}"{% endfor %}{% endif %} "{{path.path}}" "${TARGETDIR}/${HOSTN}/daily/latest/data" )
		if [ ! $? -eq 0 ] ; then
			HBCK_STATUS=1
			HBCK_MSGS+="$CR[ERROR] running rsync --delete --log-file="${TEMPLOCAL}/eholcim_bck_on_hetzner" --log-file-format='%o: %B %f %L %l %b' -avAX{% if path.exclude %}{% for xcl in path.exclude %} --exclude="{{xcl}}"{% endfor %}{% endif %} '{{path.path}}' ${TARGETDIR}/${HOSTN}/daily/latest/data $CR $RSYNC $CR"
		else
			HBCK_MSGS+="$CR[OK] rsync --delete --log-file="${TEMPLOCAL}/eholcim_bck_on_hetzner" --log-file-format='%o: %B %f %L %l %b' -avAX{% if path.exclude %}{% for xcl in path.exclude %} --exclude="{{xcl}}"{% endfor %}{% endif %} '{{path.path}}' ${TARGETDIR}/${HOSTN}/daily/latest/data $CR "
		fi

{% endfor %}
# End Ansible loop
	fi

	if ! GZIP=$( gzip ${TEMPLOCAL}/eholcim_bck_on_hetzner ) ; then
		HBCK_STATUS=1
		HBCK_MSGS+="$CR[ERROR] running gzip ${TEMPLOCAL}/eholcim_bck_on_hetzner $CR $GZIP $CR "
	else
		HBCK_MSGS+="$CR[OK] running gzip ${TEMPLOCAL}/eholcim_bck_on_hetzner $CR "

		if ! CP=$( rsync ${TEMPLOCAL}/eholcim_bck_on_hetzner.gz ${TARGETDIR}/${HOSTN}/daily/latest/logs/eholcim_bck_on_hetzner.gz ) ; then
			HBCK_STATUS=1
			HBCK_MSGS+="$CR[ERROR] running rsync ${TEMPLOCAL}/eholcim_bck_on_hetzner.gz ${TARGETDIR}/${HOSTN}/daily/latest/logs/eholcim_bck_on_hetzner.gz  $CR $CP $CR "
		else
			HBCK_MSGS+="$CR[OK] running rsync ${TEMPLOCAL}/eholcim_bck_on_hetzner.gz ${TARGETDIR}/${HOSTN}/daily/latest/logs/eholcim_bck_on_hetzner.gz  $CR $CP $CR "
		fi

	fi

	if [ $HBCK_STATUS -eq 0 ] ; then
		if RMLOCK=$( echo Y | rm ${HBCKDEVDIR}/${HOSTN}/daily/BackupDataFs.lock 2>&1 1>/dev/null ) ; then
			HBCK_MSGS+="$CR[OK] removed lockfile  ${HBCKDEVDIR}/${HOSTN}/daily/BackupDataFs.lock"
		else
			HBCK_STATUS=1
			HBCK_MSGS+="$CR[ERROR] removing lockfile ${HBCKDEVDIR}/${HOSTN}/daily/BackupDataFs.lock $CR $RMLOCK $CR "
		fi

		if ! UMSTATUS=$( bash -c "umount ${TARGETDIR} 2>&1" ) ; then
			HBCK_STATUS=1
			HBCK_MSGS+="$CR[ERROR] unmounting ${TARGETDIR}:$CR $UMSTATUS $CR"
		else
			HBCK_MSGS+="$CR[OK] umount  ${TARGETDIR} "
		fi

		if ! UMSTATUS=$( bash -c "umount ${HBCKDEVDIR} 2>&1" ) ; then
			HBCK_STATUS=1
			HBCK_MSGS+="$CR[ERROR] unmounting ${HBCKDEVDIR}:$CR $UMSTATUS $CR"
		else
			HBCK_MSGS+="$CR[OK] umount  ${HBCKDEVDIR} "
		fi
	fi

	return $HBCK_STATUS

}


if [ $(( $TODAYSEC - $LASTRUN )) -lt $WARNINTV ] ; then
	echo "This script will only run after 23h (82800s) since its last run"
	SUBJ="[ FATAL ERROR ] [${HPROJECT}] [daily Hetzner backup] [${HCUSTOMER}] [${HSITE}] [${HOSTN}:${IP}]"
	echo "Hetzner backup will only run after 23h (82800s) since its last run!" | /usr/bin/nail -s "$SUBJ" -r "${HSITE}@backup.eaudeweb.ro" ${EMAILREC}
	exit 1
fi

preparations
PREP=$?

hbackup
HETZNER=$?


if [ $PREP -eq 1 ] || [ $HETZNER -eq 1 ] ; then
	if [ $HETZNER -eq 1 ] ; then
		SUBJ="[ FATAL ERROR ] [${HPROJECT}] [daily Hetzner backup] [${HCUSTOMER}] [${HSITE}] [${HOSTN}:${IP}] "
	else
		SUBJ="[ WARNING: PARTIAL ERROR ] [${HPROJECT}] [daily Hetzner backup] [${HCUSTOMER}] [${HSITE}] [${HOSTN}:${IP}]"
	fi

	TOTAL=1
else
	SUBJ="[ OK ] [${HPROJECT}] [daily Hetzner backup] [${HCUSTOMER}] [${HSITE}] [${HOSTN}:${IP}]"
	TOTAL=0
fi

for msg in "${HBCK_MSGS[@]}" ; do
	echo -e "$msg" >> ${TEMPLOCAL}/hbckreport.txt
done

# ... please, don't ask! Read the mail RFCs...
sed -i -e 's/^M//' ${TEMPLOCAL}/hbckreport.txt

echo "" | /usr/bin/nail -s "$SUBJ" -r "${HSITE}@backup.eaudeweb.ro" -q ${TEMPLOCAL}/hbckreport.txt -a ${TEMPLOCAL}/eholcim_bck_on_hetzner.gz ${EMAILREC}

echo "#!/bin/bash
# *********************************************************************
#  This file is a simplistic way to prevent same day runs of the backup
# script. 
#   *DO NOT* edit the numbers below unless you know what you're doing
# *********************************************************************
#
# Latest backup was performed on ${TODAY}
#
# Hint: date --date='2017-01-01 16:58:59' +%s or date --date='yesterday' +%s
# or use bc and the fact that 23h = 82800s
LASTRUN=$TODAYSEC" > /etc/cron.edw/lastrun.sh


exit $TOTAL

