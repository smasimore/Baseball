import getopt, sys

print sys.argv[1:]
opts = getopt.getopt(sys.argv[1:], 'sw', ['season=', 'weights='])
print opts
