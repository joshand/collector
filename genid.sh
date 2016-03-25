#!/usr/bin/bash
filename="/tmp/collector.id"
if [ ! -f $filename ]; then
        sudo echo $(sudo dmidecode -t 4 | grep ID | sed 's/.*ID://;s/ //g') \ $(ifconfig | grep -oP 'HWaddr \K.*' | sed 's/://g') | sha256sum | awk '{print $1}' > $filename
fi

if [ -f $filename ]; then
	while read -r line
	do
		name=$line
	done < "$filename"
#	echo "Name - $name"
fi
