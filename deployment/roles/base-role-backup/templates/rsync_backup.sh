#!/bin/bash

CR=$'\n'
TABS=$'\t\t'
RSYNC='/usr/bin/rsync'
MAIL='/usr/bin/nail'
HOSTN="{{server_hostname}}"
IP="{{server_ip}}"
TODAY=$( date +%Y-%m-%d_%H-%M-%S )
TODAYSEC=$( date +%s )
WARNINTV=82800
EMAILREC="{{backup_emailrec}}"


RCUSTOMER="{{backup_rscust}}"
RPROJECT="{{backup_rsproject}}"
RSITE="{{backup_rssite}}"
RSUSER="{{backup_rsuser}}"
RSKEY="/opt/edw/system/oth/{{backup_rskey}}"
TEMPLOCAL="{{backup_rstemp}}"
RSHOST="{{backup_rshost}}"
RBCK_STATUS=0
RBCK_PARTIAL=0
declare -a RBCK_MSGS

preparations()	{

	if ! LOG=$( rm -f ${TEMPLOCAL}/* 2>&1 1>/dev/null ) ; then
		RBCK_PARTIAL=1
		RBCK_MSGS+="$CR[ERROR]  Cleaning up temp:  rm -f ${TEMPLOCAL}/* 2>&1 1>/dev/null $CR $LOG $CR"
	fi

	# Generate this with a loop from Ansible, one command per <if> e.g. dbdump and THEN gz"
	# The 2>&1 1>/dev/null must be in your command BUT if output's important
	# then send "1" to a file e.g. 
	# mysqldump --all-databases 2>&1 1>/tmp/some.sql
	#
{% for command in backup_rsprep %}
	if ! PREP=$( {{command}} ) ; then
		RBCK_PARTIAL=1
		RBCK_MSGS+="$CR[ERROR] in prep: {{command}} $CR $PREP $CR"
	fi
{% endfor %}
	
	return $RBCK_PARTIAL
	
}

rsbackup() {

	if ! MKLOG=$( touch ${TEMPLOCAL}/${RSITE}_${TODAY}.log ) ; then 
		RBCK_PARTIAL=1
		RBCK_MSGS+="$CR[ERROR]	Creating log file: touch ${TEMPLOCAL}/${RSITE}_${TODAY}.log $CR $MKLOG $CR "
	fi	

	if [ ! -x $RSYNC ] ; then
		RBCK_STATUS=1
		RBCK_MSGS+="$CR[ERROR]  Checking for rsync command $CR $RSYNC   not found $CR"
	fi

	if [ ! -x $MAIL ] ; then
		RBCK_STATUS=1
		RBCK_MSGS+="$CR[ERROR]  Checking for required email command $CR $MAIL   not found $CR"
	fi

	if ! REMOTECHK=$( ssh -i ${RSKEY} -o 'BatchMode yes' ${RSUSER}@${RSHOST} "ls ${RCUSTOMER}/${RPROJECT}/${RSITE}/data " 2>&1 1>/dev/null ) ; then
		RBCK_STATUS=1
		RBCK_MSGS+="$CR[ERROR]  ${RSUSER}@${RSHOST} could not ls  ${RCUSTOMER}/${RPROJECT}/${RSITE}/data $CR $REMOTECHK $CR"
	fi

	if [ $RBCK_STATUS -eq 0 ] ; then

		RSYNC_RSH="ssh -i ${RSKEY} -o 'BatchMode yes'" 
		export RSYNC_RSH

## Ansible loop: "for path in back_up_paths"
{% for path in backup_paths %}

		RSYNC=$( rsync --delete --log-file="${TEMPLOCAL}/${RSITE}_${TODAY}.log" --log-file-format='%o: %B %f %L %l %b' --rsync-path='rsync --fake-super' -av{% if path.exclude %}{% for xcl in path.exclude %} --exclude="{{xcl}}"{% endfor %}{% endif %} "{{path.path}}" ${RSUSER}@${RSHOST}:${RCUSTOMER}/${RPROJECT}/${RSITE}/data/ )
		if [ ! $? -eq 0 ] ; then
			RBCK_STATUS=1
			RBCK_MSGS+="$CR[ERROR] running rsync --delete --log-file="${TEMPLOCAL}/${RSITE}_${TODAY}.log" --log-file-format='%o: %B %f %L %l %b' --rsync-path='rsync --fake-super' -av{% if path.exclude %}{% for xcl in path.exclude %} --exclude="{{xcl}}"{% endfor %}{%endif%} "{{path.path}}" ${RSUSER}@${RSHOST}:${RCUSTOMER}/${RPROJECT}/${RSITE}/data/ $CR $RSYNC $CR"
		else
			RBCK_MSGS+="$CR[OK] running rsync --delete --log-file="${TEMPLOCAL}/${RSITE}_${TODAY}.log" --log-file-format='%o: %B %f %L %l %b' --rsync-path='rsync --fake-super' -av{% if path.exclude %}{% for xcl in path.exclude %} --exclude="{{xcl}}"{% endfor %}{%endif%} "{{path.path}}" ${RSUSER}@${RSHOST}:${RCUSTOMER}/${RPROJECT}/${RSITE}/data/ $CR "
		fi
{% endfor %}
## END Ansible loop
	fi
	if ! GZIP=$( gzip ${TEMPLOCAL}/${RSITE}_${TODAY}.log ) ; then 
		RBCK_STATUS=1
		RBCK_MSGS+="$CR[ERROR] running gzip ${TEMPLOCAL}/${RSITE}_${TODAY}.log $CR $GZIP $CR "
	else 
		RBCK_MSGS+="$CR[OK] running gzip ${TEMPLOCAL}/${RSITE}_${TODAY}.log $CR "
	fi

	if ! SCP=$( scp -i ${RSKEY} ${TEMPLOCAL}/${RSITE}_${TODAY}.log.gz 16496@ch-s010.rsync.net:${RCUSTOMER}/${RPROJECT}/${RSITE}/logs/${RSITE}_${TODAY}.log.gz  ) ; then 
		RBCK_STATUS=1
		RBCK_MSGS+="$CR[ERROR] running scp -i ${RSKEY} ${TEMPLOCAL}/${RSITE}_${TODAY}.log.gz 16496@ch-s010.rsync.net:${RCUSTOMER}/${RPROJECT}/${RSITE}/logs/${RSITE}_${TODAY}.log.gz $CR $SCP $CR "
	else
		RBCK_MSGS+="$CR[OK] running scp -i ${RSKEY} ${TEMPLOCAL}/${RSITE}_${TODAY}.log.gz 16496@ch-s010.rsync.net:${RCUSTOMER}/${RPROJECT}/${RSITE}/logs/${RSITE}_${TODAY}.log.gz $CR $SCP $CR "
	fi

	return $RBCK_STATUS

}

preparations
PREP=$?

rsbackup
RSYNC=$?


if [ $PREP -eq 1 ] || [ $RSYNC -eq 1 ] ; then
	if [ $RSYNC -eq 1 ] ; then
		SUBJ="[ FATAL ERROR ] [${RPROJECT}] [daily Rsync backup] [${RCUSTOMER}] [${RSITE}] [${HOSTN}:${IP}] "
	else
		SUBJ="[ WARNING: PARTIAL ERROR ] [${RPROJECT}] [daily Rsync backup] [${RCUSTOMER}] [${RSITE}] [${HOSTN}:${IP}] "
	fi

	TOTAL=1
else
	SUBJ="[ OK ] [${RPROJECT}] [daily Rsync backup] [${RCUSTOMER}] [${RSITE}] [${HOSTN}:${IP}] "
	TOTAL=0
fi

echo $SUBJ

for msg in "${RBCK_MSGS[@]}" ; do
	echo -e "$msg" >> ${TEMPLOCAL}/rbckreport.txt
done

# ... please, don't ask! Read the mail RFCs... 
sed -i -e 's/\r//' ${TEMPLOCAL}/rbckreport.txt

echo "" | /usr/bin/nail -s "$SUBJ" -r "${RSITE}@backup.eaudeweb.ro" -q ${TEMPLOCAL}/rbckreport.txt -a ${TEMPLOCAL}/${RSITE}_${TODAY}.log.gz ${EMAILREC}

exit $TOTAL

