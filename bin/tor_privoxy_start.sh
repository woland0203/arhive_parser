#!/bin/bash

#set -e #Остановим скрипт при первой ошибке

PID_END=2 #Ограничить количество ресурсов
PRIVOXY_PORT=8100 #Стартовый порт для privoxy, соответственно всего будут заняты порты с 8100 по 8199
TOR_PORT=9100 #Стартовый порт для Tor,  соответственно всего будут заняты порты с 9101 по 9199
TOR_CONTROL=20000 #Стартовый порт контроля за Tor. Данный параметр опустить нельзя, но в принципе для данной задачи он нам не нужен.
BASE_IP=127.0.0.1 #Укажем, по какому IP будет происходить коннект к нашему прокси. Это удобно если эта же тачка будет открыта в интернет.
BASE_DIR=./data_tor #Каталог с PIDами TOR
BASE_DIR_PRIVOXY=/etc #Каталог с конфигами Privoxy, замена на другой каталог должна быть согласована с 64 и 65 строчкой кода
INSTANCE=$1 #В скрипте передаем количество нужных инстансов. Это удобно если тебе не нужно сейчас 99 проксей
ACTION=$2
UPDATE="update"

if [ ! -d $BASE_DIR ]; then
	mkdir -p $BASE_DIR
fi

echo "Выберите редатор для запуска:"
echo "1 Запустить TOR и Privoxy"
echo "2 Обновить потоки TOR"
echo "3 Очистить следы TOR и Privoxy"

read doing

case $doing in
1)
    #Запускаем сервисы

    for i in $(seq 1 ${INSTANCE});
    do
    	p_port=$((PRIVOXY_PORT+i))
    	s_port=$((TOR_PORT+i))
    	c_port=$((TOR_CONTROL+i))

        if [ $i -gt $PID_END ]; then
            echo "Количество Treads превысило максимально, допустимое значение\n"
            break
        fi

    	if [ ! -d "$BASE_DIR/tor${i}" ]; then
            echo "Создаем директорию ${BASE_DIR}/tor${i}"
    		mkdir -p "${BASE_DIR}/tor${i}"
    	fi

        if [ ! -d "${BASE_DIR_PRIVOXY}/privoxy${i}" ]; then
    		echo "Создаем директорию ${BASE_DIR_PRIVOXY}/privoxy${i}"
    		mkdir -p "${BASE_DIR_PRIVOXY}/privoxy${i}"
    	fi

        cp -r ${BASE_DIR_PRIVOXY}/privoxy/* ${BASE_DIR_PRIVOXY}/privoxy$i #копируем конфигурацию из оригинала
        #Заполняем конфигурацию
        echo "" > ${BASE_DIR_PRIVOXY}/privoxy$i/config;
        echo "forward-socks4a / 127.0.0.1:${s_port} ." >> ${BASE_DIR_PRIVOXY}/privoxy$i/config
        echo "confdir ${BASE_DIR_PRIVOXY}/privoxy${i}" >> ${BASE_DIR_PRIVOXY}/privoxy$i/config
        echo "logdir /var/log/privoxy/" >> ${BASE_DIR_PRIVOXY}/privoxy$i/config
        echo "logfile privoxy.log" >> ${BASE_DIR_PRIVOXY}/privoxy$i/config
        echo "#actionsfile standard" >> ${BASE_DIR_PRIVOXY}/privoxy$i/config
        echo "actionsfile default.action" >> ${BASE_DIR_PRIVOXY}/privoxy$i/config
        echo "actionsfile user.action" >> ${BASE_DIR_PRIVOXY}/privoxy$i/config
        echo "filterfile default.filter" >> ${BASE_DIR_PRIVOXY}/privoxy$i/config
        echo "debug   4096" >> ${BASE_DIR_PRIVOXY}/privoxy$i/config
        echo "debug   8192" >> ${BASE_DIR_PRIVOXY}/privoxy$i/config
        echo "listen-address  ${BASE_IP}:${p_port}" >> ${BASE_DIR_PRIVOXY}/privoxy$i/config
        echo "user-manual /usr/share/doc/privoxy/user-manual" >> ${BASE_DIR_PRIVOXY}/privoxy$i/config
        echo "toggle  1" >> ${BASE_DIR_PRIVOXY}/privoxy$i/config
        echo "enable-remote-toggle 0" >> ${BASE_DIR_PRIVOXY}/privoxy$i/config
        echo "enable-edit-actions 0" >> ${BASE_DIR_PRIVOXY}/privoxy$i/config
        echo "enable-remote-http-toggle 0" >> ${BASE_DIR_PRIVOXY}/privoxy$i/config
        echo "buffer-limit 4096" >> ${BASE_DIR_PRIVOXY}/privoxy$i/config

        cp -r /etc/init.d/privoxy /etc/init.d/privoxy$i
        cp -r /usr/sbin/privoxy /usr/sbin/privoxy$i
        sed -i "s/NAME=privoxy/NAME=privoxy${i}/g" /etc/init.d/privoxy$i

        FROM="CONFIGFILE=\/etc\/privoxy\/config"
        TO="CONFIGFILE=\/etc\/privoxy${i}\/config"

        sed -i "s/$FROM/$TO/g" /etc/init.d/privoxy$i



    	echo "Запуск: tor --RunAsDaemon 1 --CookieAuthentication 0 --HashedControlPassword \"\" --ControlPort $c_port --PidFile tor$i.pid --SocksPort $s_port --DataDirectory ${BASE_DIR}/tor$i"

    	tor --RunAsDaemon 1 --CookieAuthentication 0 --HashedControlPassword "" --ControlPort $c_port --PidFile tor$i.pid --SocksPort $s_port --DataDirectory ${BASE_DIR}/tor$i

        echo "Update RC"
        update-rc.d privoxy$i defaults
        systemctl daemon-reload

    	#Применяем изменения
    	/etc/init.d/privoxy$i restart
    done



    #для удобства выводим запущенные прокси в файл
    netstat -4ln | grep $BASE_IP:80** | grep -Eo '10.{12}' > ./proxy_list.txt
;;
2)
    #Обновляем сервисы если это нужно
    echo "Обновляем потоки TOR\n"
    for i in $(seq 1 ${INSTANCE});
    do
        c_port=$((TOR_CONTROL+i))
        ./tor_renew.exp $c_port
    done
;;

3)
    for i in $(seq 1 ${PID_END});
    do
        rm -r /etc/privoxy${i}
        rm /etc/init.d/privoxy${i}
        rm /usr/sbin/privoxy${i}

    done

    kill $(ps aux | grep -e 'tor' -e 'privoxy' | awk '{print $2}')

;;

esac
