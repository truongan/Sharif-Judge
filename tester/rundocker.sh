
sudo cat /etc/lsb-release
sudo apt-get update
sudo apt-get install -y docker.io
sudo systemctl start docker
sudo systemctl enable docker
sudo docker pull ubuntu
sudo docker create ubuntu:16.04
sudo docker run -it -v /home/vagrant/Code/tester/$JAIL:/$JAIL:rw --name='docker' ubuntu:16.04 cd $JAIL;./runcode.sh $EXT $MEMLIMIT $TIMELIMIT $TIMELIMITINT ./in/input$i.txt "./timeout --just-kill -nosandbox -l $OUTLIMIT -t $TIMELIMIT -m $MEMLIMIT ./$EXEFILE"


sudo docker run -it -v /home/vagrant/Code/tester/jail-9152/:/jail-9152:rw --name='testdocker' ubuntu:16.04 cd jail-9152; g++ shield.cpp -o solution
sudo docker run -it -v /home/vagrant/Wecode/tester/jail-9152/:/jail-9152:rw --name='testdocker' ubuntu:16.04 cd jail-9152; g++ shield.cpp -o solution
sudo docker run -it -v /home/vagrant/Code/tester/jail-1109:/jail-1109:rw --name='testdocker' ubuntu:16.04
sudo docker stop $(sudo docker ps -a -q)
sudo docker rm $(sudo docker ps -a -q)
docker exec -it  echo "Hello from container!"