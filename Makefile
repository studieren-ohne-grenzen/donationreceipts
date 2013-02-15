VERSION := $(shell head -1 NEWS)

dist:
	(echo; git ls-files)|sed 's!^!sfe.donationreceipts/!'|( cd .. && xargs zip donationreceipts-$(VERSION).zip)
