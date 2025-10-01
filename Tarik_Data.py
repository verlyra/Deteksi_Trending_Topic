import subprocess
import sys


filename = sys.argv[1]	# "gempa.csv"
keyword  = sys.argv[2]	# "gempa lang:id"
limit    = sys.argv[3]	# 100

command = f"npx -y tweet-harvest@2.6.1 -o {filename} -s \"{keyword}\" --tab LATEST -l {limit} --token c10d07d4c941e7babe54998e95f0fd3593a60080"

subprocess.run(command, check=True, shell=True)