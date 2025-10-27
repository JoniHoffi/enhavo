<?php

namespace Enhavo\Bundle\TranslationBundle\Endpoint;

use Doctrine\ORM\EntityManagerInterface;
use Enhavo\Bundle\ApiBundle\Data\Data;
use Enhavo\Bundle\ApiBundle\Endpoint\AbstractEndpointType;
use Enhavo\Bundle\ApiBundle\Endpoint\Context;
use Enhavo\Bundle\AppBundle\Resource\ResourceManager;
use Enhavo\Bundle\TranslationBundle\Translate\TranslateManager;
use Enhavo\Component\Metadata\MetadataRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class TranslateEndpointType extends AbstractEndpointType
{
    private TranslateManager $translateManager;
    private MetadataRepository $metadataRepository;
    private ResourceManager $resourceManager;
    private EntityManagerInterface $entityManager;

    public function __construct(TranslateManager $translateManager, MetadataRepository $metadataRepository, ResourceManager $resourceManager, EntityManagerInterface $entityManager)
    {
        $this->translateManager = $translateManager;
        $this->metadataRepository = $metadataRepository;
        $this->resourceManager = $resourceManager;
        $this->entityManager = $entityManager;
    }

    public function handleRequest($options, Request $request, Data $data, Context $context)
    {
        $repository = $this->entityManager->getRepository($options['entity']);
        $entity = $repository->find($request->get('id'));
        $this->translateManager->translate($page, 'en');





        $repository = $this->entityManager->getRepository($options['entity']);
        $propertyAccessor = new PropertyAccessor();

        if ($repository) {
            $entity = $repository->find($request->get('id'));
            $metadata = $this->metadataRepository->getMetadata($entity);

            if ($metadata !== null) {
                $this->translateManager->translateMetadataProperties($entity, $metadata);
                $this->resourceManager->save($entity);
            }

            if ($propertyAccessor->isReadable($entity, 'content')) {
                $content = $propertyAccessor->getValue($entity, 'content');
                foreach ($content->getChildren() as $child) {
                    $block = $child->getBlock();
                    $metadata = $this->metadataRepository->getMetadata($block);

                    if ($metadata !== null) {
                        $this->translateManager->translateMetadataProperties($block, $metadata);
                        $this->resourceManager->save($entity);
                    }

                    if (in_array('App\Model\ItemsAwareInterface', class_implements($block))) {
                        foreach ($block->getItems() as $item) {
                            $metadata = $this->metadataRepository->getMetadata($item);

                            if ($metadata !== null) {
                                $this->translateManager->translateMetadataProperties($item, $metadata);
                                $this->resourceManager->save($entity);
                            }
                        }
                    }
                }
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
    }
}
