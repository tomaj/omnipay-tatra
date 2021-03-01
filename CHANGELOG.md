# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

 - Added CardPay tests
 - Removed travis support
 - Added support for github workflows
 - Fix syntax errors

## [4.0.0]

 - Updated ComfortPay implementation according new WSDL defined by TB. 
 - Breaking changes in ChargeRequest: 
    - parameter `cid` renamed to `referedCardId` 
    - added new mandatory parameter `transactionType`.
