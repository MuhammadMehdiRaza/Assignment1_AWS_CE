import boto3
import botocore.exceptions

ec2_client = boto3.client('ec2')


ec2_client.create_vpc(
    "amazon_provided_ipv6cidr_block": false,
    "instance_tenancy": "default",
    "cidr_block": "10.0.0.0/16",
    "tag_specifications": [{"resource_type": "vpc", "tags": [{"key": "Name", "value": "Assignment-1"}]}]
)
