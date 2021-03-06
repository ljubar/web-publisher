<?php

/*
 * This file is part of the Superdesk Web Publisher MultiTenancyBundle.
 *
 * Copyright 2016 Sourcefabric z.u. and contributors.
 *
 * For the full copyright and license information, please see the
 * AUTHORS and LICENSE files distributed with this source code.
 *
 * @copyright 2016 Sourcefabric z.ú
 * @license http://www.superdesk.org/license
 */

namespace SWP\Bundle\MultiTenancyBundle\Command;

use Doctrine\Common\Persistence\ObjectManager;
use SWP\Component\MultiTenancy\Model\OrganizationInterface;
use SWP\Component\MultiTenancy\Model\TenantInterface;
use SWP\Component\MultiTenancy\Repository\OrganizationRepositoryInterface;
use SWP\Component\MultiTenancy\Repository\TenantRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Class CreateTenantCommand.
 */
class CreateTenantCommand extends ContainerAwareCommand
{
    /**
     * @var array
     */
    protected $arguments = ['domain', 'subdomain', 'name', 'organization code'];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('swp:tenant:create')
            ->setDescription('Creates a new tenant.')
            ->setDefinition([
                new InputArgument($this->arguments[3], InputArgument::OPTIONAL, 'Organization code'),
                new InputArgument($this->arguments[0], InputArgument::OPTIONAL, 'Domain name'),
                new InputArgument($this->arguments[2], InputArgument::OPTIONAL, 'Tenant name'),
                new InputArgument($this->arguments[1], InputArgument::OPTIONAL, 'Subdomain name', null),
                new InputOption('disabled', null, InputOption::VALUE_NONE, 'Set the tenant as a disabled'),
                new InputOption('default', null, InputOption::VALUE_NONE, 'Creates the default tenant'),
            ])
            ->setHelp(
                <<<'EOT'
                The <info>%command.name%</info> command creates a new tenant.
EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $domain = $input->getArgument($this->arguments[0]);
        $subdomain = $input->getArgument($this->arguments[1]);
        $name = $input->getArgument($this->arguments[2]);
        $organizationCode = $input->getArgument($this->arguments[3]);
        $default = $input->getOption('default');
        $disabled = $input->getOption('disabled');

        if ($default) {
            $name = TenantInterface::DEFAULT_TENANT_NAME;
            $domain = $this->getContainer()->getParameter('env(SWP_DOMAIN)');
            $organization = $this->getOrganizationRepository()->findOneByName(OrganizationInterface::DEFAULT_NAME);
            if (null === $organization) {
                throw new \InvalidArgumentException('Default organization doesn\'t exist!');
            }
        } else {
            $organization = $this->getOrganizationRepository()->findOneByCode($organizationCode);

            if (null === $organization) {
                throw new \InvalidArgumentException(sprintf('Organization with "%s" code doesn\'t exist!', $organizationCode));
            }
        }

        if (null !== $subdomain) {
            $tenant = $this->getTenantRepository()->findOneBySubdomainAndDomain($subdomain, $domain);
        } else {
            $tenant = $this->getTenantRepository()->findOneByDomain($domain);
        }

        if (null !== $tenant) {
            throw new \InvalidArgumentException(sprintf('Tenant with domain %s and subdomain "%s" already exists!', $domain, $subdomain));
        }

        $tenant = $this->createTenant($domain, $subdomain, $name, $disabled, $organization);

        $this->getObjectManager()->persist($tenant);
        $this->getObjectManager()->flush();

        $output->writeln(
            sprintf(
                'Tenant <info>%s</info> (code: <info>%s</info>) has been created and <info>%s</info>!',
                $name,
                $tenant->getCode(),
                $tenant->isEnabled() ? 'enabled' : 'disabled'
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->arguments as $value) {
            $this->askAndValidateInteract($input, $output, $value);
        }
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param string          $name
     */
    protected function askAndValidateInteract(InputInterface $input, OutputInterface $output, $name)
    {
        $default = $input->getOption('default');
        if (!$input->getArgument($name) && !$default && $name !== $this->arguments[1]) {
            $question = new Question(sprintf('<question>Please enter %s:</question>', $name));
            $question->setValidator(function ($argument) use ($name) {
                if (empty($argument)) {
                    throw new \RuntimeException(sprintf('The %s can not be empty', $name));
                }

                return $argument;
            });

            $question->setMaxAttempts(3);

            $argument = $this->getHelper('question')->ask($input, $output, $question);

            $input->setArgument($name, $argument);
        }
    }

    /**
     * Creates a new tenant.
     *
     * @param $subdomain
     * @param $name
     * @param $disabled
     * @param $organization
     *
     * @return TenantInterface
     */
    protected function createTenant($domain, $subdomain, $name, $disabled, $organization)
    {
        $tenantFactory = $this->getContainer()->get('swp.factory.tenant');
        /** @var TenantInterface $tenant */
        $tenant = $tenantFactory->create();
        $tenant->setSubdomain($subdomain);
        $tenant->setDomainName($domain);
        $tenant->setName($name);
        $tenant->setEnabled(!$disabled);
        $tenant->setOrganization($organization);

        return $tenant;
    }

    /**
     * @return ObjectManager
     */
    protected function getObjectManager()
    {
        return $this->getContainer()->get('swp.object_manager.tenant');
    }

    /**
     * @return TenantRepositoryInterface
     */
    protected function getTenantRepository()
    {
        return $this->getContainer()->get('swp.repository.tenant');
    }

    /**
     * @return OrganizationRepositoryInterface
     */
    protected function getOrganizationRepository()
    {
        return $this->getContainer()->get('swp.repository.organization');
    }
}
