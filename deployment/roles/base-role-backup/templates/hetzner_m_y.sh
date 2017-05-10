#!/bin/bash

CR=$'\n'
TABS=$'\t\t'
RSYNC='/usr/bin/rsync'
AR='/usr/bin/tar'
MOUNTCIFS='/usr/sbin/mount.cifs'
MAIL='/usr/bin/nail'
HOSTN="{{server_hostname}}"
IP="{{server_ip}}"
TODAY=$( date +%Y-%m-%d_%H-%M-%S )
TODAYSEC=$( date +%s )
EMAILREC="{{backup_emailrec}}"


HCUSTOMER="{{backup_hzcust}}"
HPROJECT="{{backup_hzproject}}"
HSITE="{{backup_hzsite}}"
HBCKMORY="{{backup_hzmory}}"
HBCKUSR="{{backup_hzuser}}"
HBCKPWD="{{backup_hzpwd}}"
HBCKDEV="//${HBCKUSR}.your-backup.de/backup"
HBCKDEVDIR="{{backup_hzbckdevdir}}"
TARGETDIR="{{backup_hztargetdir}}"
TEMPLOCAL="{{backup_hztemp}}"
HBCK_PARTIAL=0
HBCK_STATUS=0
declare -a HBCK_MSGS

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
	if ! MKLOG=$( touch  ${TEMPLOCAL}/${HCUSTOMER}_${HPROJECT}_${HSITE}_${TODAY}.log ) ; then
		HBCK_PARTIAL=1
		RBCK_MSGS+="$CR[ERROR]  Creating log file: touch ${TEMPLOCAL}/${HCUSTOMER}_${HPROJECT}_${HSITE}_${TODAY}.log $CR $MKLOG $CR "
	fi

	if [ ! -d "${TARGETDIR}" ] ; then
		HBCK_STATUS=1
		HBCK_MSGS+="$CR[ERROR]  Checking ${TARGETDIR} $CR ${TARGETDIR} mountpoint does not exist $CR"
	fi

	if [ ! -x $RSYNC ] ; then
		HBCK_STATUS=1
		HBCK_MSGS+="$CR[ERROR]  Checking for rsync command $CR $RSYNC not found $CR"
	fi

	if [ ! -x $TAR ] ; then
		HBCK_STATUS=1
		HBCK_MSGS+="$CR[ERROR]  Checking for tar command $CR $TAR not found $CR"
	fi

	if [ ! -x $MOUNTCIFS ] ; then
		HBCK_STATUS=1
		HBCK_MSGS+="$CR[ERROR]  Checking for mount.cifs command $CR $MOUNTCIFS not found $CR"
	fi

	if [ ! -x $MAIL ] ; then
		HBCK_STATUS=1
		HBCK_MSGS+="$CR[ERROR]  Checking for required email command $CR $MAIL not found $CR"
	fi

	DEVCHK=$( mount | grep "$HBCKDEVDIR" )
	DEVCHKSTATUS=$?

	if [ $DEVCHKSTATUS -eq 0 ]  ; then
		HBCK_STATUS=1
		if [ ! -z "${DEVCHK}" ] ; then
			HBCK_MSGS+="$CR[ERROR] Target dir $HBCKDEVDIR is busy $CR $DEVCHK $CR"
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

	fi

	if [ $HBCK_STATUS -eq 0 ] ; then

## Ansible loop: "tar for path in back_up_paths"

		# RMDUS: Real_Men_Don_t_Use_Spaces :->
		TARGZ=$( tar -czvf ${HBCKDEVDIR}/${HOSTN}/{{backup_hzmory}}/${HCUSTOMER}_${HPROJECT}_${HSITE}_${TODAY}.tgz {% for path in backup_paths %}{% if path.exclude %}{% for xcl in path.exclude %} --exclude="{{xcl}}"{% endfor %}{% endif %}{% endfor %} {% for path in backup_paths %} "{{path.path}}"{% endfor %} 1>> ${TEMPLOCAL}/${HCUSTOMER}_${HPROJECT}_${HSITE}_${TODAY}.log 2>&1 )
		if [ ! $? -eq 0 ] ; then
			HBCK_STATUS=1
			HBCK_MSGS+="$CR[ERROR] running tar -czvf ${HBCKDEVDIR}/${HOSTN}/${HBCKMORY}/${HCUSTOMER}_${HPROJECT}_${HSITE}_${TODAY}.tgz {% for path in backup_paths %}{% if path.exclude %}{% for xcl in path.exclude %} --exclude="{{xcl}}"{% endfor %}{% endif %}{% endfor %} {% for path in backup_paths %} "{{path.path}}"{% endfor %} 1>> ${TEMPLOCAL}/${HCUSTOMER}_${HPROJECT}_${HSITE}_${TODAY}.log 2>&1 $CR $TARGZ $CR"
		else
			HBCK_MSGS+="$CR[OK] tar -czvf ${HBCKDEVDIR}/${HOSTN}/${HBCKMORY}/${HCUSTOMER}_${HPROJECT}_${HSITE}_${TODAY}.tgz {% for path in backup_paths %}{% if path.exclude %}{% for xcl in path.exclude %} --exclude="{{xcl}}"{% endfor %}{% endif %}{% endfor %} {% for path in backup_paths %} "{{path.path}}"{% endfor %} 1>> ${TEMPLOCAL}/${HCUSTOMER}_${HPROJECT}_${HSITE}_${TODAY}.log 2>&1 $CR "
		fi

# End Ansible loop
	fi

	if ! GZIP=$( gzip ${TEMPLOCAL}/${HCUSTOMER}_${HPROJECT}_${HSITE}_${TODAY}.log ) ; then
		HBCK_STATUS=1
		HBCK_MSGS+="$CR[ERROR] running gzip ${TEMPLOCAL}/${HCUSTOMER}_${HPROJECT}_${HSITE}_${TODAY}.log $CR $GZIP $CR "
	else
		HBCK_MSGS+="$CR[OK] running gzip ${TEMPLOCAL}/${HCUSTOMER}_${HPROJECT}_${HSITE}_${TODAY}.log $CR "

		if ! CP=$( rsync ${TEMPLOCAL}/${HCUSTOMER}_${HPROJECT}_${HSITE}_${TODAY}.log.gz ${HBCKDEVDIR}/${HOSTN}/{{backup_hzmory}}/${HCUSTOMER}_${HPROJECT}_${HSITE}_${TODAY}.log.gz ) ; then
			HBCK_STATUS=1
			HBCK_MSGS+="$CR[ERROR] running rsync ${TEMPLOCAL}/${HCUSTOMER}_${HPROJECT}_${HSITE}_${TODAY}.log.gz ${HBCKDEVDIR}/${HOSTN}/${HBCKMORY}/${HCUSTOMER}_${HPROJECT}_${HSITE}_${TODAY}.log.gz $CR $CP $CR "
		else
			HBCK_MSGS+="$CR[OK] running rsync ${TEMPLOCAL}/${HCUSTOMER}_${HPROJECT}_${HSITE}_${TODAY}.log.gz ${HBCKDEVDIR}/${HOSTN}/${HBCKMORY}/${HCUSTOMER}_${HPROJECT}_${HSITE}_${TODAY}.log.gz $CR $CP $CR "
		fi

	fi

	if [ $HBCK_STATUS -eq 0 ] ; then

		if ! UMSTATUS=$( bash -c "umount ${HBCKDEVDIR} 2>&1" ) ; then
			HBCK_STATUS=1
			HBCK_MSGS+="$CR[ERROR] unmounting ${HBCKDEVDIR}:$CR $UMSTATUS $CR"
		else
			HBCK_MSGS+="$CR[OK] umount  ${HBCKDEVDIR} "
		fi
	fi

	return $HBCK_STATUS

}

preparations
PREP=$?

hbackup
HETZNER=$?


if [ $PREP -eq 1 ] || [ $HETZNER -eq 1 ] ; then
	if [ $HETZNER -eq 1 ] ; then
		SUBJ="[ FATAL ERROR ] [${HPROJECT}] [{{backup_hzmory}} Hetzner backup] [${HCUSTOMER}] [${HSITE}] [${HOSTN}:${IP}] "
	else
		SUBJ="[ WARNING: PARTIAL ERROR ] [${HPROJECT}] [{{backup_hzmory}} Hetzner backup] [${HCUSTOMER}] [${HSITE}] [${HOSTN}:${IP}]"
	fi

	TOTAL=1
else
	SUBJ="[ OK ] [${HPROJECT}] [{{backup_hzmory}} Hetzner backup] [${HCUSTOMER}] [${HSITE}] [${HOSTN}:${IP}]"
	TOTAL=0
fi

for msg in "${HBCK_MSGS[@]}" ; do
	echo -e "$msg" >> ${TEMPLOCAL}/${HCUSTOMER}_${HPROJECT}_${HSITE}_${TODAY}.txt
done

# ... please, don't ask! Read the mail RFCs...
sed -i -e 's/^M//' ${TEMPLOCAL}/${HCUSTOMER}_${HPROJECT}_${HSITE}_${TODAY}.txt

echo "" | /usr/bin/nail -s "$SUBJ" -r "${HSITE}@backup.eaudeweb.ro" -q ${TEMPLOCAL}/${HCUSTOMER}_${HPROJECT}_${HSITE}_${TODAY}.txt -a ${TEMPLOCAL}/${HCUSTOMER}_${HPROJECT}_${HSITE}_${TODAY}.log.gz ${EMAILREC}


exit $TOTAL

