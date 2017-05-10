# Wildlex deployment

Ensure a decent value for `max_allowed_packet`, for instance: `max_allowed_packet=768M`

## Installation

ansible-playbook -b -u cristiroma --ask-vault-pass -i hosts.test install.yml

## Edit the vault

ansible-vault edit roles/wildlex/defaults/main_vault.yml